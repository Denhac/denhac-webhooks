<?php

namespace App\WinDSX;


class BackupWinDSX
{
    public function backup($path)
    {
        try {
            mkdir($path, 0777, true);
        } catch (\ErrorException $errorException) {
            report($errorException);
        }

        $command = "scp -r denhac-access:/C:/WinDSX {$path}";
        $output = [];
        $return_value = 0;
        exec($command, $output, $return_value);

        if($return_value != 0) {
            throw new \Exception("WinDSX backup failed (" . $return_value . "): " . implode(", ", $output));
        }
    }
}
