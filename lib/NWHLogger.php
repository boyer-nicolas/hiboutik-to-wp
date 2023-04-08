<?php

namespace Niwee\Niwhiboutik;

class NWHLogger
{
    public function __construct()
    {
        $this->nwh_dir = str_replace('lib/', '', plugin_dir_path(__FILE__));
        $this->log_file = $this->nwh_dir . '/import.log';
    }

    public static function write($message)
    {
        sleep(0.5);
        Self::clear();
        $self = new self();
        return file_put_contents($self->log_file, $message . PHP_EOL, FILE_APPEND);
    }

    public static function latest_log()
    {
        $self = new self();
        $line = '';

        $f = fopen($self->log_file, 'r');
        $cursor = -1;

        fseek($f, $cursor, SEEK_END);
        $char = fgetc($f);

        /**
         * Trim trailing newline chars of the file
         */
        while ($char === "\n" || $char === "\r")
        {
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }

        /**
         * Read until the start of file or first newline char
         */
        while ($char !== false && $char !== "\n" && $char !== "\r")
        {
            /**
             * Prepend the new char
             */
            $line = $char . $line;
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }

        fclose($f);

        return $line;
    }

    public static function clear()
    {
        $self = new self();
        return file_put_contents($self->log_file, '');
    }
}
