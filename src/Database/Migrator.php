<?php

namespace Nepf2\Database;

class Migrator extends \Pop\Db\Sql\Migrator
{
    private ?string $currentFile = null;

    /**
     * @return string
     */
    public function getCurrentFile(): string
    {
        return is_null($this->currentFile) ? $this->path . DIRECTORY_SEPARATOR . '.current' : $this->currentFile;
    }

    /**
     * @param string|null $currentFile
     */
    public function setCurrentFile(string|null $currentFile): void
    {
        $this->currentFile = $currentFile;
        $this->loadCurrent();
    }

    /**
     * Load the current migration timestamp
     *
     * @return void
     */
    protected function loadCurrent(): void
    {
        $this->current = file_exists($this->getCurrentFile()) ? (int)file_get_contents($this->getCurrentFile()) : null;
    }

    /**
     * Store the current migration timestamp
     *
     * @param int $current
     * @param string $classFile
     * @param int|null $batch
     * @return void
     */
    protected function storeCurrent(int $current, string $classFile, ?int $batch = null): void
    {
        file_put_contents($this->getCurrentFile(), $current);
        $this->current = $current;
    }

    /**
     * Delete migration
     *
     * @param  int  $current
     * @param  ?int $previous
     * @return void
     */
    protected function deleteCurrent(int $current, ?int $previous = null): void
    {
        if ($previous !== null) {
            $this->storeCurrent($previous, "");
        } else {
            $this->clearCurrent();
        }
    }

    /**
     * Clear migrations
     *
     * @return void
     */
    protected function clearCurrent(): void
    {
        if (file_exists($this->getCurrentFile())) {
            unlink($this->getCurrentFile());
        }

        $this->current = null;
    }
}