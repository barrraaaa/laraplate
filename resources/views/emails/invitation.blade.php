<x-mail::message>
# You're invited to {{ $organizationName }}

@if($inviterName)
{{ $inviterName }} invited you to join **{{ $organizationName }}** as a {{ $role }}.
@else
You've been invited to join **{{ $organizationName }}** as a {{ $role }}.
@endif

Click the button below to accept the invitation.

<x-mail::button :url="$acceptUrl">
Accept invitation
</x-mail::button>

This invitation expires on {{ $expiresAt }}. If you weren't expecting this invitation, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>