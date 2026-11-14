<?php

namespace App\Http\Controllers;

use App\Mail\MailAppPassword;
use App\Services\MailAuthAlert;
use App\Services\MailPasswordStore;
use Illuminate\Http\Request;

class MailPasswordController extends Controller
{
    public function update(Request $request)
    {
        $token = (string) config('app.mail_admin_token');
        $given = (string) $request->header('X-Mail-Admin-Token', '');
        if ($token === '' || $given === '' || !hash_equals($token, $given)) {
            return response()->json(['ok' => false], 403);
        }

        $password = MailAppPassword::normalize($request->input('password', ''));
        if ($password === null || !MailPasswordStore::probe($password)) {
            return response()->json(['ok' => false], 422);
        }

        MailPasswordStore::persist($password);
        MailAuthAlert::clear();

        return response()->json(['ok' => true]);
    }
}
