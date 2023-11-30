<?php


use Hashids\Hashids;

function encodeId($id)
{

    $hashids = new Hashids(config('app.key'), 10); // Adjust the second parameter as needed
    return $hashids->encode($id);
}

function decodeId($hash)
{
    $hashids = new Hashids(config('app.key'), 10); // Adjust the second parameter as needed
    $decoded = $hashids->decode($hash);
    return count($decoded) > 0 ? $decoded[0] : null;
}