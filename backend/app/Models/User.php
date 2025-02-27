<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, SoftDeletes, HasUlids;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Generate and return a new access token. Create a new session record if the latest session hasn't been signed out.
     * @return string
     */
    public function login() {
        // Generate new access token
        $token = $this->createToken('auth_token')->plainTextToken;

        // Create a new session record if there's no active session record
        $latestSessionRecord = SessionRecord::where('user_id', $this->id)->latest('login_at')->first();
        if (!$latestSessionRecord || $latestSessionRecord->logout_at) {
            // Create new session record
            SessionRecord::create([
                'user_id' => $this->id,
                'login_at' => now(),
            ]);
        }

        return $token;
    }

    /**
     * Destroy all active access tokens and sign out from the latest session record.
     * @return void
     */
    public function logout() {
        // Destroy all access tokens
        $this->tokens()->delete();

        // Sign out the latest session record
        $latestSessionRecord = SessionRecord::where('user_id', $this->id)->latest('login_at')->first();
        $latestSessionRecord->logout_at = now();
        $latestSessionRecord->save();
    }

    /**
     * Get the last login time from session records.
     * @return string
     */
    public function lastLoginAt() {
        $latestSessionRecord = SessionRecord::where('user_id', $this->id)->latest('login_at')->first();

        /**
         * @var string
         */
        $loginAt = $latestSessionRecord->login_at;
        return $loginAt;
    }

    /**
     * Get the total time the user spent logged in the system.
     * @return string
     */
    public function totalLoginTime(): int
    {
        // Get all session records for the current user
        $sessionRecords = $this->sessionRecords()->get();

        /** @var float|int|string */
        $totalLoginTime = 0;

        // Separate active and signed-out records
        $activeRecord = null;
        $signedOutRecords = [];

        foreach ($sessionRecords as $record) {
            if ($record->login_at && $record->logout_at) {
                // Signed out record
                $signedOutRecords[] = $record;
            } else if ($record->login_at && !$record->logout_at) {
                // Currently active record
                if ($activeRecord) {
                    // Anomaly: more than one active record
                    logger()->warning('Anomaly: Multiple active session records found for user ID: ' . $this->id);
                } else {
                    $activeRecord = $record;
                }
            } else {
                // Anomaly: record with logout_at defined but login_at undefined
                logger()->warning('Anomaly: Record with anomalous timestamps at ID ' . $record->id . ' for user ID: ' . $this->id);
            }
        }

        // Calculate total time for signed-out records
        foreach ($signedOutRecords as $record) {
            $totalLoginTime += Carbon::createFromFormat('Y-m-d H:i:s', $record->logout_at)->timestamp - Carbon::createFromFormat('Y-m-d H:i:s', $record->login_at)->timestamp;
        }

        // Calculate time for the currently active record
        if ($activeRecord) {
            $totalLoginTime += now()->timestamp - Carbon::createFromFormat('Y-m-d H:i:s', $activeRecord->login_at)->timestamp;
        }

        return $totalLoginTime; // Total login time in seconds
    }

    public function sessionRecords()
    {
        return $this->hasMany(SessionRecord::class);
    }

    public function student() {
        return $this->belongsTo(Student::class);
    }
    public function teacher() {
        return $this->belongsTo(Teacher::class);
    }
    public function coordinator() {
        return $this->belongsTo(Coordinator::class);
    }
    public function admin() {
        return $this->belongsTo(Admin::class);
    }
}
