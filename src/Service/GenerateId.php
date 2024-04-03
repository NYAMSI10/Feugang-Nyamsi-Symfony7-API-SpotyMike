<?php

namespace App\Service;


class GenerateId
{
    public function randId()
    {
        $chars = "1234ABCDRUJLQOOCE56789";
        return substr(str_shuffle($chars), 0, 4);
    }
}
