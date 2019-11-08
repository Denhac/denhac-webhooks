<?php

namespace App\WinDSX;


class BackupWinDSX
{
    public function backup($path)
    {
        mkdir($path, 0777, true);

        $command = "scp -r denhac-access:/C:/WinDSX {$path}";
        exec($command); # TODO Handle failures.
    }
}
