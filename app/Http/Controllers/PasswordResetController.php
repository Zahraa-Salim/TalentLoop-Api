<?php 
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
    {
        // Validate the email input
        $request->validate(['email' => 'required|email']);

        // Attempt to send the password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Return appropriate response based on the status
        return $status === Password::RESET_LINK_SENT
                    ? response()->json(['message' => __($status)], 200)
                    : response()->json(['message' => __($status)], 400);
    }
    public function logResetRequest(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Log the password reset request
        Log::info('Password reset requested for: ' . $request->email);

        return response()->json(['message' => 'Password reset logged.'], 200);
    }
}
