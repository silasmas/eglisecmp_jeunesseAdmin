---

**Équipe Jeunesse CMP**  
Centre Missionnaire Philadelphie — Kinshasa, RDC

@if(filled(config('retraite.mail_reply_to')))
Pour toute question, répondez directement à cet e-mail ou écrivez à **{{ config('retraite.mail_reply_to') }}**.
@endif

@if($showSecurityHint ?? false)
<x-mail::panel>
Si vous n'êtes pas à l'origine de cette demande, ignorez ce message. Ne communiquez jamais votre code de vérification à une tierce personne.
</x-mail::panel>
@endif
