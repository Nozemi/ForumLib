<?php
    namespace ForumLib\Utilities;

    use ForumLib\Utilities\Config;

    class Logger {
        const INFO    = 0;
        const WARNING = 1;
        const ERROR   = 2;
        const DEBUG   = 3;

        protected $message;
        protected $type;
        protected $file;
        protected $line;

        /**
         * Logger constructor.
         *
         * @param $message
         * @param $type
         * @param $file
         * @param $line
         */
        public function __construct($message, $type, $file = null, $line = null) {
            $this->message = $message;
            $this->type    = $type;
            $this->file    = basename($file);
            $this->line    = $line;

            switch($this->type) {
                case self::INFO:
                    $this->info();
                    break;
                case self::WARNING:
                    $this->warning();
                    break;
                case self::ERROR:
                    $this->error();
                    break;
                case self::DEBUG:
                    $this->debug();
                    break;
            }

            $this->create();
        }

        /**
         * @return bool|string
         */
        private function create() {
            $config = new Config;
            $logLevel = $config->getConfigValue('logLevel');
            $logSize  = $config->getConfigValue('logSize');

            if(is_array($logLevel)) {
                if (!in_array('info', $logLevel) && $this->type == self::INFO) {
                    return false;
                }

                if(!in_array('debug', $logLevel) && $this->type == self::DEBUG) {
                    return false;
                }

                if(!in_array('warning', $logLevel) && $this->type == self::WARNING) {
                    return false;
                }

                if(!in_array('error', $logLevel) && $this->type == self::ERROR) {
                    return false;
                }
            }

            $timestamp = \Date('m/d Y - H:i:s');

            $logFile = MISC::findFile('logs/eldrios.log');

            try {
                // Open and write to the file if it doesn't exist.

                if(!file_exists($logFile)) {
                    $accessFile = fopen($logFile, "w");
                } else {
                    if((filesize($logFile) / 1024) > (!empty($logSize) ? $logSize : 1000)) {
                        $accessFile = fopen($logFile, "w");
                    } else {
                        $accessFile = fopen($logFile, "a");
                    }
                }

                $text = "[{$timestamp}][{$this->file}][{$this->line}]: {$this->message}\r\n";
                fwrite($accessFile, $text);
                fclose($accessFile);
            } catch(\Exception $ex) {
                if(defined('DEBUG')) {
                    echo $ex->getMessage();
                } else {
                    echo "Looks like the logger doesn\'t have access to log to [{$logFile}].";
                }
            }

            return true;
        }

        private function debug() {
            $this->message = '[DEBUG]: ' . $this->message;
        }

        private function warning() {
            $this->message = '[WARNING]: ' . $this->message;
        }

        private function error() {
            $this->message = '[ERROR]: ' . $this->message;
        }

        private function info() {
            $this->message = '[INFO]: ' . $this->message;
        }
    }