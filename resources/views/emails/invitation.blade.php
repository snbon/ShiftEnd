@component('mail::message')
# You're Invited to Join {{ $location->name }} on ShiftEnd

Hello,

{{ $inviter->name }} ({{ $inviter->email }}) has invited you to join the team at **{{ $location->name }}** as a
**{{ ucfirst($invitation->role) }}**.

@component('mail::button', ['url' => url('/invite/' . $invitation->invite_code)])
Accept Invitation
@endcomponent

This invitation will expire on {{ $invitation->expires_at->format('F j, Y') }}.

If you did not expect this invitation, you can ignore this email.

Thanks,<br>
The ShiftEnd Team
@endcomponent
