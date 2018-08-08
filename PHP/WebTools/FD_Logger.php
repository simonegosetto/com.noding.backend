<?php

final class FD_Logger
{

	private $log_file, $fp;

	public function __construct($path)
    {
		$this->log_file = $path;
	}

    public function lwrite($message)
    {
		// if file pointer doesn't exist, then open log file
		if (!is_resource($this->fp))
        {
			$this->lopen();
		}
		// define script name
		$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
		// define current time and suppress E_WARNING if using the system TZ settings
		// (don't forget to set the INI setting date.timezone)
		$time = @date('[d/m/Y:H:i:s]');
        /*
            colorazione in base al tipo di log
        */
        if(strpos($message,"ERRORE") !== false)
        {
            $message = str_replace("[ERRORE]","<b style=\"color: red\">[ERRORE]</b>",$message);
        }
        else if (strpos($message,"DENIED") !== false)
        {
            $message = str_replace("[DENIED]","<b>[DENIED]</b>",$message);
        }
		// write current time, script name and message to the log file
		fwrite($this->fp, "$time ($script_name) $message" . PHP_EOL);

        fclose($this->fp);
	}

	public function force_close()
    {
		fclose($this->fp);
	}

	private function lopen()
    {
		/*
        // in case of Windows set default log file
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$log_file_default = 'c:/php/logfile.txt';
		}
		// set default log file for Linux and other systems
		else {
			$log_file_default = '/tmp/logfile.txt';
		}*/
        $log_file_default = 'Log/'.@date('d_m_Y').'.txt';
		$lfile = $this->log_file != null ? $this->log_file : $log_file_default;
		$this->fp = fopen($lfile, 'a'); //or exit("Can't open $lfile!");
	}
}
