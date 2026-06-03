@component('mail::message')
# You're invited to join {{ $invitation->project->name }}!

**{{ $invitation->invitedBy->name }}** has invited you to join the project **{{ $invitation->project->name }}** on ProjectPilot AI as a **{{ $invitation->role }}**.

@component('mail::button', ['url' => $acceptUrl, 'color' => 'blue'])
Accept Invitation
@endcomponent

This invitation will expire in **7 days**.

If you don't have an account yet, you'll be asked to create one.

Thanks,
**ProjectPilot AI Team**
@endcomponent