<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $channel = $this->input('channel');

        $recipientRules = ['required', 'array', 'min:1', 'max:1000'];
        $recipientItemRules = ['required', 'string', 'max:255'];

        if ($channel === NotificationChannel::Email->value) {
            $recipientItemRules[] = 'email';
        }

        if ($channel === NotificationChannel::Sms->value) {
            $recipientItemRules[] = 'regex:/^\+[1-9]\d{6,14}$/';
        }

        return [
            'channel' => ['required', 'string', Rule::enum(NotificationChannel::class)],
            'message' => ['required', 'string', 'min:1', 'max:1000'],
            'recipients' => $recipientRules,
            'recipients.*' => $recipientItemRules,
            'type' => ['nullable', 'string', Rule::enum(NotificationType::class)],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ];
    }
}
