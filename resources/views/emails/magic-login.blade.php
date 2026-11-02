<x-mail::message>
# Your login link

Click the button below to sign in instantly — no password needed.

<x-mail::button :url="$loginUrl">
Log in to {{ config('app.name') }}
</x-mail::button>

This link expires at {{ $expiresAt }}. If you didn't request it, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>