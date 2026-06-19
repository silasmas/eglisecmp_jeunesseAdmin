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

    $cards = [
      ['key' => 'participants', 'label' => 'Participants', 'value' => $participants, 'tone' => 'neutral', 'icon' => '👥', 'share' => null],
      ['key' => 'present', 'label' => 'Présents', 'value' => (int) ($totals['present'] ?? 0), 'tone' => 'success', 'icon' => '✓', 'share' => $percentOf((int) ($totals['present'] ?? 0))],
      ['key' => 'late', 'label' => 'Retards', 'value' => (int) ($totals['late'] ?? 0), 'tone' => 'warning', 'icon' => '⏱', 'share' => $percentOf((int) ($totals['late'] ?? 0))],
      ['key' => 'absent', 'label' => 'Absents', 'value' => (int) ($totals['absent'] ?? 0), 'tone' => 'danger', 'icon' => '✕', 'share' => $percentOf((int) ($totals['absent'] ?? 0))],
      ['key' => 'excused', 'label' => 'Excusés', 'value' => (int) ($totals['excused'] ?? 0), 'tone' => 'info', 'icon' => '📝', 'share' => $percentOf((int) ($totals['excused'] ?? 0))],
      ['key' => 'unmarked', 'label' => 'Non pointés', 'value' => (int) ($totals['unmarked'] ?? 0), 'tone' => 'muted', 'icon' => '○', 'share' => $percentOf((int) ($totals['unmarked'] ?? 0))],
    ];
  @endphp

  <div class="cmp-presence-overview">
    <article class="cmp-presence-global">
      <div class="cmp-presence-global__head">
        <div>
          <p class="cmp-presence-global__eyebrow">Total global</p>
          <h3 class="cmp-presence-global__title">{{ number_format($participants, 0, ',', ' ') }} participant(s)</h3>
          <p class="cmp-presence-global__meta">
            {{ $ateliersCount }} atelier(s) · {{ number_format($marked, 0, ',', ' ') }} pointé(s) · {{ number_format($presentEffective, 0, ',', ' ') }} présent(s) ou en retard
          </p>
          @if(!empty($activity['label']))
            <p class="cmp-presence-global__activity">{{ $activity['label'] }}</p>
          @endif
        </div>
        <div class="cmp-presence-global__rates">
          <div class="cmp-presence-rate cmp-presence-rate--primary">
            <span class="cmp-presence-rate__label">Taux de présence</span>
            <strong class="cmp-presence-rate__value">{{ number_format($presentRate, 1, ',', ' ') }} %</strong>
          </div>
          <div class="cmp-presence-rate">
            <span class="cmp-presence-rate__label">Taux de pointage</span>
            <strong class="cmp-presence-rate__value">{{ number_format($pointageRate, 1, ',', ' ') }} %</strong>
          </div>
        </div>
      </div>

      <div class="cmp-presence-progress" aria-hidden="true">
        <span class="cmp-presence-progress__segment cmp-presence-progress__segment--present" style="width: {{ $participants > 0 ? (($totals['present'] ?? 0) / $participants) * 100 : 0 }}%"></span>
        <span class="cmp-presence-progress__segment cmp-presence-progress__segment--late" style="width: {{ $participants > 0 ? (($totals['late'] ?? 0) / $participants) * 100 : 0 }}%"></span>
        <span class="cmp-presence-progress__segment cmp-presence-progress__segment--absent" style="width: {{ $participants > 0 ? (($totals['absent'] ?? 0) / $participants) * 100 : 0 }}%"></span>
        <span class="cmp-presence-progress__segment cmp-presence-progress__segment--excused" style="width: {{ $participants > 0 ? (($totals['excused'] ?? 0) / $participants) * 100 : 0 }}%"></span>
        <span class="cmp-presence-progress__segment cmp-presence-progress__segment--unmarked" style="width: {{ $participants > 0 ? (($totals['unmarked'] ?? 0) / $participants) * 100 : 0 }}%"></span>
      </div>

      <div class="cmp-presence-legend">
        <span><i class="dot dot--present"></i> Présents</span>
        <span><i class="dot dot--late"></i> Retards</span>
        <span><i class="dot dot--absent"></i> Absents</span>
        <span><i class="dot dot--excused"></i> Excusés</span>
        <span><i class="dot dot--unmarked"></i> Non pointés</span>
      </div>
    </article>

    <div class="cmp-presence-stats-grid">
      @foreach($cards as $card)
        <article class="cmp-presence-stat cmp-presence-stat--{{ $card['tone'] }}">
          <div class="cmp-presence-stat__icon" aria-hidden="true">{{ $card['icon'] }}</div>
          <div class="cmp-presence-stat__body">
            <p class="cmp-presence-stat__label">{{ $card['label'] }}</p>
            <p class="cmp-presence-stat__value">{{ number_format($card['value'], 0, ',', ' ') }}</p>
            @if($card['share'] !== null)
              <p class="cmp-presence-stat__share">{{ $card['share'] }} du total</p>
            @else
              <p class="cmp-presence-stat__share">Référence globale</p>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  </div>
@endif
