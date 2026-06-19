@if(!empty($totals))
  <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
      <div class="text-xs text-gray-500">Participants</div>
      <div class="text-xl font-semibold">{{ $totals['participants'] ?? 0 }}</div>
    </div>
    <div class="rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-900 dark:bg-green-950">
      <div class="text-xs text-green-700">Présents</div>
      <div class="text-xl font-semibold text-green-800">{{ $totals['present'] ?? 0 }}</div>
    </div>
    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950">
      <div class="text-xs text-amber-700">Retards</div>
      <div class="text-xl font-semibold text-amber-800">{{ $totals['late'] ?? 0 }}</div>
    </div>
    <div class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-900 dark:bg-red-950">
      <div class="text-xs text-red-700">Absents</div>
      <div class="text-xl font-semibold text-red-800">{{ $totals['absent'] ?? 0 }}</div>
    </div>
    <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-900 dark:bg-blue-950">
      <div class="text-xs text-blue-700">Excusés</div>
      <div class="text-xl font-semibold text-blue-800">{{ $totals['excused'] ?? 0 }}</div>
    </div>
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
      <div class="text-xs text-gray-500">Non pointés</div>
      <div class="text-xl font-semibold">{{ $totals['unmarked'] ?? 0 }}</div>
    </div>
  </div>
@endif
