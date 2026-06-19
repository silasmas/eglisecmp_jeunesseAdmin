@if(!empty($totals))
  @php
    $participants = (int) ($totals['participants'] ?? 0);
    $presentEffective = (int) ($totals['present_effective'] ?? (($totals['present'] ?? 0) + ($totals['late'] ?? 0)));
    $marked = (int) ($totals['marked'] ?? ($participants - ($totals['unmarked'] ?? 0)));
    $presentRate = (float) ($totals['present_rate'] ?? ($participants > 0 ? round(($presentEffective / $participants) * 100, 1) : 0));
    $pointageRate = (float) ($totals['pointage_rate'] ?? ($participants > 0 ? round(($marked / $participants) * 100, 1) : 0));
    $ateliersCount = (int) ($totals['ateliers_count'] ?? 0);

    $percentOf = static function (int $value) use ($participants): string {
      if ($participants <= 0) {
        return '0 %';
      }

      return number_format(($value / $participants) * 100, 1, ',', ' ').' %';
    };

    $stats = [
      ['label' => 'Présents', 'value' => (int) ($totals['present'] ?? 0), 'tone' => 'present', 'share' => $percentOf((int) ($totals['present'] ?? 0))],
      ['label' => 'Retards', 'value' => (int) ($totals['late'] ?? 0), 'tone' => 'late', 'share' => $percentOf((int) ($totals['late'] ?? 0))],
      ['label' => 'Absents', 'value' => (int) ($totals['absent'] ?? 0), 'tone' => 'absent', 'share' => $percentOf((int) ($totals['absent'] ?? 0))],
      ['label' => 'Excusés', 'value' => (int) ($totals['excused'] ?? 0), 'tone' => 'excused', 'share' => $percentOf((int) ($totals['excused'] ?? 0))],
      ['label' => 'Non pointés', 'value' => (int) ($totals['unmarked'] ?? 0), 'tone' => 'unmarked', 'share' => $percentOf((int) ($totals['unmarked'] ?? 0))],
    ];
  @endphp

  <div class="cmp-presence-bar">
    <div class="cmp-presence-bar__row">
      <div class="cmp-presence-bar__total">
        <span class="cmp-presence-bar__eyebrow">Total global</span>
        <div class="cmp-presence-bar__total-main">
          <strong class="cmp-presence-bar__total-value">{{ number_format($participants, 0, ',', ' ') }}</strong>
          <span class="cmp-presence-bar__total-label">participant(s)</span>
        </div>
        <span class="cmp-presence-bar__total-meta">
          {{ $ateliersCount }} atelier(s) · {{ number_format($marked, 0, ',', ' ') }} pointé(s)
        </span>
        @if(!empty($activity['label']))
          <span class="cmp-presence-bar__activity" title="{{ $activity['label'] }}">{{ $activity['label'] }}</span>
        @endif
      </div>

      <div class="cmp-presence-bar__divider" aria-hidden="true"></div>

      <div class="cmp-presence-bar__stats" role="list">
        @foreach($stats as $stat)
          <article class="cmp-presence-bar__stat cmp-presence-bar__stat--{{ $stat['tone'] }}" role="listitem">
            <span class="cmp-presence-bar__stat-dot" aria-hidden="true"></span>
            <div class="cmp-presence-bar__stat-body">
              <span class="cmp-presence-bar__stat-label">{{ $stat['label'] }}</span>
              <strong class="cmp-presence-bar__stat-value">{{ number_format($stat['value'], 0, ',', ' ') }}</strong>
              <span class="cmp-presence-bar__stat-share">{{ $stat['share'] }}</span>
            </div>
          </article>
        @endforeach
      </div>

      <div class="cmp-presence-bar__divider" aria-hidden="true"></div>

      <div class="cmp-presence-bar__rates">
        <div class="cmp-presence-bar__rate cmp-presence-bar__rate--primary">
          <span class="cmp-presence-bar__rate-label">Présence</span>
          <strong class="cmp-presence-bar__rate-value">{{ number_format($presentRate, 1, ',', ' ') }} %</strong>
          <span class="cmp-presence-bar__rate-hint">{{ number_format($presentEffective, 0, ',', ' ') }} / {{ number_format($participants, 0, ',', ' ') }}</span>
        </div>
        <div class="cmp-presence-bar__rate">
          <span class="cmp-presence-bar__rate-label">Pointage</span>
          <strong class="cmp-presence-bar__rate-value">{{ number_format($pointageRate, 1, ',', ' ') }} %</strong>
          <span class="cmp-presence-bar__rate-hint">{{ number_format($marked, 0, ',', ' ') }} / {{ number_format($participants, 0, ',', ' ') }}</span>
        </div>
      </div>
    </div>

    <div class="cmp-presence-bar__progress" aria-hidden="true">
      <span class="cmp-presence-bar__segment cmp-presence-bar__segment--present" style="width: {{ $participants > 0 ? (($totals['present'] ?? 0) / $participants) * 100 : 0 }}%"></span>
      <span class="cmp-presence-bar__segment cmp-presence-bar__segment--late" style="width: {{ $participants > 0 ? (($totals['late'] ?? 0) / $participants) * 100 : 0 }}%"></span>
      <span class="cmp-presence-bar__segment cmp-presence-bar__segment--absent" style="width: {{ $participants > 0 ? (($totals['absent'] ?? 0) / $participants) * 100 : 0 }}%"></span>
      <span class="cmp-presence-bar__segment cmp-presence-bar__segment--excused" style="width: {{ $participants > 0 ? (($totals['excused'] ?? 0) / $participants) * 100 : 0 }}%"></span>
      <span class="cmp-presence-bar__segment cmp-presence-bar__segment--unmarked" style="width: {{ $participants > 0 ? (($totals['unmarked'] ?? 0) / $participants) * 100 : 0 }}%"></span>
    </div>
  </div>
@endif
