@php
  $buttonUrl = trim((string) ($url ?? ''));
  $buttonLabel = trim((string) ($label ?? ''));
  $buttonColor = trim((string) ($color ?? '#D4772C'));
@endphp

@if($buttonUrl !== '' && $buttonLabel !== '')
  <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:20px auto;">
    <tr>
      <td align="center" bgcolor="{{ $buttonColor }}" style="border-radius:8px;mso-padding-alt:12px 24px;">
        <a
          href="{{ $buttonUrl }}"
          target="_blank"
          rel="noopener noreferrer"
          style="display:inline-block;padding:12px 24px;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:700;line-height:1.2;color:#ffffff !important;text-decoration:none;border-radius:8px;background-color:{{ $buttonColor }};"
        >
          {{ $buttonLabel }}
        </a>
      </td>
    </tr>
  </table>

  <p style="margin:0 0 16px;text-align:center;font-size:12px;line-height:1.5;color:#6e6058;font-family:Arial,Helvetica,sans-serif;">
    Si le bouton ne s'ouvre pas, copiez ce lien dans votre navigateur :<br>
    <a href="{{ $buttonUrl }}" target="_blank" rel="noopener noreferrer" style="color:#D4772C;word-break:break-all;text-decoration:underline;">{{ $buttonUrl }}</a>
  </p>
@endif
