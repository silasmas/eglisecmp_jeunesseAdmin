@php
  $logoUrl = $logoUrl ?? \App\Support\RetreatMailUrl::base().'/retraite-inscription/img/logo.jpg';
@endphp

<p align="center" style="margin: 0 0 24px;">
  <img
    src="{{ $logoUrl }}"
    alt="Jeunesse CMP — Centre Missionnaire Philadelphie"
    width="100"
    height="auto"
    style="border-radius: 12px; display: inline-block; max-width: 100%;"
  >
</p>

<p align="center" style="margin: 0 0 20px; color: #6f6471; font-size: 13px; line-height: 1.5;">
  Jeunesse CMP · Centre Missionnaire Philadelphie
</p>
