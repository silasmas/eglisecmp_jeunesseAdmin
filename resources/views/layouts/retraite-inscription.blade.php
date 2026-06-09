<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="retraite-api-base" content="{{ url('/api/v1/retreat/inscription') }}">
  <title>@yield('title', "Inscription — Grande Retraite de la jeunesse | CMP")</title>
  <meta name="description" content="@yield('meta_description', 'Formulaire d\'inscription officiel pour la Grande Retraite de la jeunesse du Centre Missionnaire Philadelphie.')">

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
  <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>

  @php
    $r = asset('retraite-inscription');
    $rv = static function (string $path) use ($r): string {
        $fullPath = public_path('retraite-inscription/'.$path);
        $version = file_exists($fullPath) ? filemtime($fullPath) : time();

        return "{$r}/{$path}?v={$version}";
    };
  @endphp
  <link rel="stylesheet" href="{{ $r }}/css/tokens.css">
  <link rel="stylesheet" href="{{ asset('cmp-portail/css/cmp-layout.css') }}">
  <link rel="stylesheet" href="{{ asset('cmp-portail/css/cmp-footer.css') }}">
  <link rel="stylesheet" href="{{ $rv('css/splash.css') }}">
  <link rel="stylesheet" href="{{ $r }}/css/base.css">
  <link rel="stylesheet" href="{{ $r }}/css/stepper.css">
  <link rel="stylesheet" href="{{ $rv('css/form.css') }}">
  <link rel="stylesheet" href="{{ $r }}/css/datepicker.css">
  <link rel="stylesheet" href="{{ $r }}/css/uploads.css">
  <link rel="stylesheet" href="{{ $r }}/css/payment.css">
  <link rel="stylesheet" href="{{ $r }}/css/recap.css">
  <link rel="stylesheet" href="{{ $r }}/css/badge.css">
  <link rel="stylesheet" href="{{ $r }}/css/buttons.css">
  <link rel="stylesheet" href="{{ $r }}/css/utilities.css">
  @stack('styles')
</head>
<body>
  <div class="cmp-page-shell">
    @yield('content')
    @include('partials.cmp-portail.footer')
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="{{ $rv('js/notifications.js') }}"></script>
  <script src="{{ $r }}/js/registration-api.js"></script>
  <script src="{{ $r }}/js/state.js"></script>
  <script src="{{ $rv('js/form-config.js') }}"></script>
  <script src="{{ $rv('js/funnel-tracking.js') }}"></script>
  <script src="{{ $r }}/js/stepper.js"></script>
  <script src="{{ $r }}/js/validation.js"></script>
  <script src="{{ $r }}/js/phone-live-validation.js"></script>
  <script src="{{ $r }}/js/datepicker.js"></script>
  <script src="{{ $r }}/js/uploads.js"></script>
  <script src="{{ $r }}/js/recap.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  {{-- Ne pas utiliser cdnjs pour jspdf 2.5.2/jspdf.umd.min.js : URL en 404 ; jsDelivr OK --}}
  <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.2/dist/jspdf.umd.min.js"></script>
  <script src="{{ $r }}/js/badge.js"></script>
  <script src="{{ $r }}/js/storage.js"></script>
  <script src="{{ $rv('js/app.js') }}"></script>
  @stack('scripts')
</body>
</html>
