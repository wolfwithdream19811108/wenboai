<?php

namespace app\imageai\service;

use think\Db;

class BillingService
{
    public function reserve($userId, $points)
    {
        $points = max(0, (int)$points);
        if ($points === 0) {
            return true;
        }
        return Db::name('user_points')
            ->where('userid', (int)$userId)
            ->where('points', '>=', $points)
            ->setDec('points', $points) > 0;
    }

    public function refund($userId, $points)
    {
        $points = max(0, (int)$points);
        if ($points > 0) {
            Db::name('user_points')->where('userid', (int)$userId)->setInc('points', $points);
        }
    }
}
