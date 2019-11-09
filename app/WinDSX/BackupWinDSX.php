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
        exec($command); # TODO Handle failures.
    }
}
