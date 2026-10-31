<?php
// modified by jaoe1000
declare(strict_types = 1);

function get_partner($player = 0) :string
{
    global $session;
    $player = (int)$player;
    $accounts = db_prefix('accounts');
    if ($player <> 0) {
        $sql = db_query(
            "SELECT a.sex, b.name FROM $accounts AS a
            LEFT JOIN $accounts AS b ON a.marriedto = b.acctid
            WHERE a.acctid = '$player'"
        );
        $row  = db_fetch_assoc($sql);
        if (!empty($row['name'])) {
            $partner = (string)$row['name'];
        }
        else {
            db_query(
                "UPDATE $accounts SET marriedto = '0'
                WHERE acctid = '$player'"
            );
            $partner = (string)(
                !empty($row['sex']) ?
                getsetting('bard', '`^Seth') :
                getsetting('barmaid', '`%Violet')
            );
        }
    }
    else {
        $marriedTo = (int)($session['user']['marriedto'] ?? 0);
        $sql = db_query(
            "SELECT name FROM $accounts
            WHERE acctid = '$marriedTo'"
        );
        if ($row = db_fetch_assoc($sql)) {
            $partner = (string)$row['name'];
        }
        else {
            $session['user']['marriedto'] = 0;
            $partner = (string)(
                !empty($session['user']['sex']) ?
                getsetting('bard', '`^Seth') :
                getsetting('barmaid', '`%Violet')
            );
        }
    }
    return $partner;
}
