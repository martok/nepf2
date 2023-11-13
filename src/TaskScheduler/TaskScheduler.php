<?php

namespace Nepf2\TaskScheduler;

use Nepf2\Application;
use Nepf2\IComponent;
use Nepf2\Util\Arr;
use Nepf2\Util\ClassUtil;
use Nepf2\Util\Time;
use Psr\Log\LoggerInterface;

class TaskScheduler implements IComponent
{
    public const ComponentName = "tasks";
    private Application $app;

    public const STALE_TASKS_TIMEOUT = 60 * 60 * 1000;
    private LoggerInterface $logger;

    public function __construct(Application $application)
    {
        $this->app = $application;
        $this->logger = $application->getLogChannel('TaskScheduler');
    }

    public function configure(array $config)
    {
        $config = Arr::ExtendConfig([
            'log_'
        ], $config);
        ScheduledTasks::CreateTable($this->app->db);
    }

    /**
     * @return ScheduledTasks[]
     */
    private function markElapsedTasks(): array
    {
        ScheduledTasks::db()->beginTransaction();
        $mark = Time::Millis();
        // Mark tasks as being worked on by us
        $q = ScheduledTasks::db()->createSql();
        $q->update(ScheduledTasks::table())
          ->set('started', ':mark')
          ->where('started IS NULL')
          ->andWhere('deadline <= :now');
        ScheduledTasks::execute($q, ['mark' => $mark, 'now' => time()]);
        try {
            ScheduledTasks::db()->commit();
        } catch (\PDOException $exc) {
            // if the commit failed, someone made a change and is probably working on it - skip
            return [];
        }
        // get the tasks we marked as an array
        $elapsed = ScheduledTasks::findBy(['started' => $mark]);
        return [... $elapsed];
    }

    private function unmarkTasks(array $tasks)
    {
        foreach ($tasks as $task) {
            $task->started = null;
            $task->save();
        }
    }

    private function unmarkStaleTasks()
    {
        $q = ScheduledTasks::db()->createSql();
        $q->update(ScheduledTasks::table())
            ->set('started', null)
            ->where('started < :timeout');
        ScheduledTasks::execute($q, ['timeout' => Time::Millis() - self::STALE_TASKS_TIMEOUT]);
    }

    public function run(?int $maxRuntimeMs = null)
    {
        $this->unmarkStaleTasks();
        $elapsed = $this->markElapsedTasks();
        $stopMs = Time::Millis() + $maxRuntimeMs ?? 0;
        while ($task = array_pop($elapsed)) {
            try {
                $success = $this->runTask($task);
                if ($success && !is_null($task->interval)) {
                    $task->deadline = time() + $task->interval;
                    $task->started = null;
                    $task->save();
                } else {
                    $task->delete();
                }
            } catch (\Exception $exception) {
                $this->handleError($task, $exception);
            }
            if (!is_null($maxRuntimeMs) && (Time::Millis() > $stopMs))
                break;
        }
        $this->unmarkTasks($elapsed);
    }

    protected function runTask(ScheduledTasks $task): bool
    {
        try {
            $arguments = json_decode($task->arguments, flags: JSON_THROW_ON_ERROR);
            $this->logger->notice('Running task', [$task->class, $task->method ?? '', $arguments]);
        } catch (\JsonException $e) {
            $this->logger->error('Failed task with bad arguments', [$task->toArray()]);
            return false;
        }
        $classname = $task->class;
        if (!ClassUtil::IsClass($classname))
            return false;
        if (empty($task->method)) {
            $obj = new $classname($this->app, ...$arguments);
        } else {
            if (!method_exists($classname, $task->method))
                return false;
            $obj = new $classname($this->app);
            call_user_func_array([$obj, $task->method], $arguments);
        }
        return true;
    }

    protected function handleError(ScheduledTasks $task, \Exception $exception)
    {
        $this->logger->critical('Failed with exception', ['task' => $task->toArray(), 'exception' => (string)$exception]);
        $task->delete();
    }

    protected function parseCallable(string|array $callable): array
    {
        if (is_array($callable))
            return $callable;
        return [$callable, null];
    }

    protected function scheduleTask(string|array $callable, array $arguments, int $timeout, ?int $interval): bool
    {
        [$class, $method] = $this->parseCallable($callable);
        $args = json_encode($arguments);
        // If the same task already exists, don't reschedule it
        $existing = ScheduledTasks::getTotal(['class' => $class, 'method' => $method, 'arguments' => $args]);
        if ($existing)
            return false;
        $task = new ScheduledTasks([
            'created' => time(),
            'deadline' => time() + $timeout,
            'interval' => $interval,
            'class' => $class,
            'method' => $method,
            'arguments' => $args
        ]);
        $task->save();
        return true;
    }

    public function scheduleOnce(string|array $callable, array $arguments, int $timeout): bool
    {
        return $this->scheduleTask($callable, $arguments, $timeout, null);
    }

    public function scheduleInterval(string|array $callable, array $arguments, int $interval): bool
    {
        return $this->scheduleTask($callable, $arguments, $interval, $interval);
    }

    public function stopTask(string|array $callable): bool
    {
        [$class, $method] = $this->parseCallable($callable);
        $tasks = ScheduledTasks::findBy(['class' => $class, 'method' => $method]);
        foreach ($tasks as $task) {
            $task->delete();
        }
        return true;
    }
}