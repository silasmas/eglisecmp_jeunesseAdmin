import React, { useEffect, useMemo, useRef, useState, useCallback } from 'react';
import type { ChangeEvent, PointerEvent } from 'react';
import BadgeLayerSkeleton from './BadgeLayerSkeleton';
import { fetchAllParticipants } from './api';
import {
  ELEMENT_ICONS,
  ELEMENT_LABELS,
  FONT_OPTIONS,
  INITIAL_LAYOUT,
  PHOTO_SHAPES,
  TEXT_COLORS,
} from './constants';
import ExportJobOverlay from './ExportJobOverlay';
import { preloadCaptureEngine } from './exportBadge';
import type { BadgeElement, EditScope, ExportFormat, LayoutItem, Participant, PhotoShape } from './types';
import { useBadgeExportJob } from './useBadgeExportJob';
import { useBadgePreviewReady } from './useBadgePreviewReady';
import { useWaitForPreviewReady } from './useWaitForPreviewReady';
import { clamp, copyLayout, getInitials, itemStyle, mapApiParticipant } from './utils';

interface BadgeStudioAppProps {
  templateUrl: string;
  apiParticipantsUrl: string;
}

/**
 * Studio de génération des badges participants (données API Laravel).
 */
export default function BadgeStudioApp({ templateUrl, apiParticipantsUrl }: BadgeStudioAppProps) {
  const [participants, setParticipants] = useState<Participant[]>([]);
  const [activeParticipantId, setActiveParticipantId] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [loadError, setLoadError] = useState('');
  const [layout, setLayout] = useState(INITIAL_LAYOUT);
  const [participantLayouts, setParticipantLayouts] = useState<Record<string, Record<BadgeElement, LayoutItem>>>({});
  const [editScope, setEditScope] = useState<EditScope>('global');
  const [selectedElement, setSelectedElement] = useState<BadgeElement>('name');
  const [dragInfo, setDragInfo] = useState<{
    element: BadgeElement;
    startX: number;
    startY: number;
    initialX: number;
    initialY: number;
  } | null>(null);
  const [resizeInfo, setResizeInfo] = useState<{
    element: BadgeElement;
    startX: number;
    startY: number;
    initialW: number;
    initialH: number;
  } | null>(null);
  const [templateImage, setTemplateImage] = useState(templateUrl);
  const [nameFont, setNameFont] = useState(FONT_OPTIONS[0].value);
  const [photoShape, setPhotoShape] = useState<PhotoShape>('square');
  const [exportFormat, setExportFormat] = useState<ExportFormat>('png');
  const [zoom, setZoom] = useState(100);
  const [nameColor, setNameColor] = useState('#1c1c1c');
  const [numberColor, setNumberColor] = useState('#8c1418');
  const [badgeWidth, setBadgeWidth] = useState(920);

  // Search & Filter States
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedRoom, setSelectedRoom] = useState('all');
  const [selectedWorkshop, setSelectedWorkshop] = useState('all');

  // Multi-select & Bulk Export States
  const [selectedParticipantIds, setSelectedParticipantIds] = useState<string[]>([]);
  const [isCaptureMode, setIsCaptureMode] = useState(false);

  const badgeRef = useRef<HTMLDivElement>(null);
  const templateInputRef = useRef<HTMLInputElement>(null);

  // Monitor container width changes to dynamically scale font size
  useEffect(() => {
    if (!badgeRef.current) return;
    const observer = new ResizeObserver(entries => {
      for (const entry of entries) {
        setBadgeWidth(entry.contentRect.width || 920);
      }
    });
    observer.observe(badgeRef.current);
    return () => observer.disconnect();
  }, []);

  const refreshParticipantsFromApi = useCallback(async () => {
    if (!apiParticipantsUrl) {
      setLoadError('URL API participants manquante.');
      setIsLoading(false);
      return;
    }

    setIsLoading(true);
    setLoadError('');

    try {
      const rows = await fetchAllParticipants(apiParticipantsUrl);
      const mapped = rows.map((row, index) => mapApiParticipant(row, index));
      setParticipants(mapped);
      setActiveParticipantId(prev => {
        if (mapped.length === 0) {
          return '';
        }
        if (mapped.some(item => item.id === prev)) {
          return prev;
        }
        return mapped[0].id;
      });
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Erreur de chargement.';
      setLoadError(message);
      setParticipants([]);
      setActiveParticipantId('');
    } finally {
      setIsLoading(false);
    }
  }, [apiParticipantsUrl]);

  useEffect(() => {
    refreshParticipantsFromApi();
  }, [refreshParticipantsFromApi]);

  useEffect(() => {
    preloadCaptureEngine();
  }, []);

  /* ── Derived state ── */
  const rooms = useMemo(() => {
    const set = new Set(participants.map(p => p.chambre));
    return Array.from(set).sort();
  }, [participants]);

  const workshops = useMemo(() => {
    const set = new Set(participants.map(p => p.atelier));
    return Array.from(set).sort((a, b) => a - b);
  }, [participants]);

  const filteredParticipants = useMemo(() => {
    return participants.filter(p => {
      const nameMatch = `${p.prenom} ${p.nom}`.toLowerCase().includes(searchQuery.toLowerCase());
      const roomMatch = selectedRoom === 'all' || p.chambre === selectedRoom;
      const workshopMatch = selectedWorkshop === 'all' || String(p.atelier) === selectedWorkshop;
      return nameMatch && roomMatch && workshopMatch;
    });
  }, [participants, searchQuery, selectedRoom, selectedWorkshop]);

  const activeParticipant = useMemo(
    () => participants.find(participant => participant.id === activeParticipantId) ?? participants[0] ?? null,
    [participants, activeParticipantId]
  );

  const isPreviewLoading = useBadgePreviewReady(activeParticipant, templateImage);
  const waitForPreviewReady = useWaitForPreviewReady(isPreviewLoading, activeParticipantId, badgeRef);
  const showBadgeLayers = !isPreviewLoading || isCaptureMode;

  const {
    exportJob,
    isExportLocked,
    exportCurrentBadge,
    exportSelectedBadges,
    printCurrentBadge,
    printSelectedBadges,
    requestCancelExport,
    dismissExportJob,
  } = useBadgeExportJob({
    badgeRef,
    participants,
    activeParticipant,
    activeParticipantId,
    setActiveParticipantId,
    exportFormat,
    waitForPreviewReady,
    setCaptureMode: setIsCaptureMode,
  });

  const selectParticipant = useCallback((participantId: string) => {
    if (isExportLocked) {
      return;
    }

    setActiveParticipantId(participantId);
  }, [isExportLocked]);

  const effectiveLayout = activeParticipant
    ? (participantLayouts[activeParticipant.id] ?? layout)
    : layout;
  const selectedLayout = effectiveLayout[selectedElement];
  const hasCustomLayout = activeParticipant ? !!participantLayouts[activeParticipant.id] : false;

  const updateLayout = useCallback((element: BadgeElement, next: Partial<LayoutItem>) => {
    if (!activeParticipant) {
      return;
    }

    if (editScope === 'participant') {
      setParticipantLayouts(prev => {
        const base = prev[activeParticipant.id] ?? copyLayout(layout);
        return {
          ...prev,
          [activeParticipant.id]: {
            ...base,
            [element]: { ...base[element], ...next },
          },
        };
      });
      return;
    }

    setLayout(prev => ({
      ...prev,
      [element]: { ...prev[element], ...next },
    }));
  }, [editScope, activeParticipant?.id, layout]);

  const resetGlobalLayout = () => {
    setLayout(INITIAL_LAYOUT);
    setParticipantLayouts({});
  };

  const resetParticipantLayout = () => {
    if (!activeParticipant) {
      return;
    }

    setParticipantLayouts(prev => {
      const next = { ...prev };
      delete next[activeParticipant.id];
      return next;
    });
  };

  /* ── Center helpers ── */
  const centerHorizontally = () => {
    const item = effectiveLayout[selectedElement];
    updateLayout(selectedElement, { x: (100 - item.w) / 2 });
  };

  const centerVertically = () => {
    const item = effectiveLayout[selectedElement];
    updateLayout(selectedElement, { y: (100 - item.h) / 2 });
  };

  const alignPreset = (preset: 'top-left' | 'top-center' | 'top-right' | 'left' | 'center' | 'right' | 'bottom-left' | 'bottom-center' | 'bottom-right') => {
    const item = effectiveLayout[selectedElement];
    let nextX = item.x;
    let nextY = item.y;
    
    switch (preset) {
      case 'top-left':
        nextX = 5;
        nextY = 5;
        break;
      case 'top-center':
        nextX = (100 - item.w) / 2;
        nextY = 5;
        break;
      case 'top-right':
        nextX = 100 - item.w - 5;
        nextY = 5;
        break;
      case 'left':
        nextX = 5;
        nextY = (100 - item.h) / 2;
        break;
      case 'center':
        nextX = (100 - item.w) / 2;
        nextY = (100 - item.h) / 2;
        break;
      case 'right':
        nextX = 100 - item.w - 5;
        nextY = (100 - item.h) / 2;
        break;
      case 'bottom-left':
        nextX = 5;
        nextY = 100 - item.h - 5;
        break;
      case 'bottom-center':
        nextX = (100 - item.w) / 2;
        nextY = 100 - item.h - 5;
        break;
      case 'bottom-right':
        nextX = 100 - item.w - 5;
        nextY = 100 - item.h - 5;
        break;
    }
    
    updateLayout(selectedElement, {
      x: Number(nextX.toFixed(1)),
      y: Number(nextY.toFixed(1)),
    });
  };

  /* ── Drag handling ── */
  const handlePointerDown = (event: React.PointerEvent<HTMLDivElement>, element: BadgeElement) => {
    event.stopPropagation();
    event.currentTarget.setPointerCapture(event.pointerId);
    setSelectedElement(element);
    const item = effectiveLayout[element];
    setDragInfo({
      element,
      startX: event.clientX,
      startY: event.clientY,
      initialX: item.x,
      initialY: item.y,
    });
  };

  const handlePointerMove = (event: React.PointerEvent<HTMLDivElement>) => {
    if (!dragInfo || !badgeRef.current) return;
    event.stopPropagation();
    const rect = badgeRef.current.getBoundingClientRect();
    const dx = event.clientX - dragInfo.startX;
    const dy = event.clientY - dragInfo.startY;
    const dxPercent = (dx / rect.width) * 100;
    const dyPercent = (dy / rect.height) * 100;
    const item = effectiveLayout[dragInfo.element];
    const nextX = clamp(dragInfo.initialX + dxPercent, 0, 100 - item.w);
    const nextY = clamp(dragInfo.initialY + dyPercent, 0, 100 - item.h);
    updateLayout(dragInfo.element, {
      x: Number(nextX.toFixed(1)),
      y: Number(nextY.toFixed(1)),
    });
  };

  const handlePointerUp = (event: React.PointerEvent<HTMLDivElement>) => {
    if (!dragInfo) return;
    event.stopPropagation();
    try {
      event.currentTarget.releasePointerCapture(event.pointerId);
    } catch {}
    setDragInfo(null);
  };

  /* ── Resize handling ── */
  const handleResizeStart = (event: React.PointerEvent<HTMLDivElement>, element: BadgeElement) => {
    event.stopPropagation();
    event.currentTarget.setPointerCapture(event.pointerId);
    const item = effectiveLayout[element];
    setResizeInfo({
      element,
      startX: event.clientX,
      startY: event.clientY,
      initialW: item.w,
      initialH: item.h,
    });
  };

  const handleResizeMove = (event: React.PointerEvent<HTMLDivElement>) => {
    if (!resizeInfo || !badgeRef.current) return;
    event.stopPropagation();
    const rect = badgeRef.current.getBoundingClientRect();
    const dx = event.clientX - resizeInfo.startX;
    const dy = event.clientY - resizeInfo.startY;
    const dwPercent = (dx / rect.width) * 100;
    const dhPercent = (dy / rect.height) * 100;
    const item = effectiveLayout[resizeInfo.element];
    const newW = clamp(resizeInfo.initialW + dwPercent, 5, 100 - item.x);
    const newH = clamp(resizeInfo.initialH + dhPercent, 2, 100 - item.y);
    updateLayout(resizeInfo.element, {
      w: Number(newW.toFixed(1)),
      h: Number(newH.toFixed(1)),
    });
  };

  const handleResizeUp = (event: React.PointerEvent<HTMLDivElement>) => {
    if (!resizeInfo) return;
    event.stopPropagation();
    try {
      event.currentTarget.releasePointerCapture(event.pointerId);
    } catch {}
    setResizeInfo(null);
  };

  /* ── Zoom ── */
  const zoomIn = () => setZoom(prev => Math.min(prev + 10, 150));
  const zoomOut = () => setZoom(prev => Math.max(prev - 10, 60));
  const zoomReset = () => setZoom(100);

  /* ── Template upload ── */
  const handleTemplateUpload = (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = loadEvent => {
      if (typeof loadEvent.target?.result === 'string') {
        setTemplateImage(loadEvent.target.result);
      }
    };
    reader.readAsDataURL(file);
  };

  const allFilteredAreSelected = useMemo(() => {
    if (filteredParticipants.length === 0) return false;
    return filteredParticipants.every(p => selectedParticipantIds.includes(p.id));
  }, [filteredParticipants, selectedParticipantIds]);

  const toggleSelectAllFiltered = () => {
    if (allFilteredAreSelected) {
      const filteredIds = filteredParticipants.map(p => p.id);
      setSelectedParticipantIds(prev => prev.filter(id => !filteredIds.includes(id)));
    } else {
      const newIds = filteredParticipants.map(p => p.id);
      setSelectedParticipantIds(prev => {
        const unique = new Set([...prev, ...newIds]);
        return Array.from(unique);
      });
    }
  };

  /* ── Drag coordinates display ── */
  const dragCoords = dragInfo ? effectiveLayout[dragInfo.element] : null;
  const draggingElement = dragInfo?.element || null;

  /* ═════════════════════════════
     RENDER
  ═════════════════════════════ */
  if (isLoading && participants.length === 0) {
    return (
      <main className="badge-studio-page">
        <section className="badge-studio-shell" style={{ display: 'block', padding: '2rem' }}>
          <div className="badge-studio-panel">
            <p>Chargement des participants…</p>
          </div>
        </section>
      </main>
    );
  }

  if (!activeParticipant && !isLoading) {
    return (
      <main className="badge-studio-page">
        <section className="badge-studio-shell" style={{ display: 'block', padding: '2rem' }}>
          <div className="badge-studio-panel">
            <h1 style={{ marginTop: 0 }}>Studio badges</h1>
            {loadError && <p className="badge-export-status" style={{ color: '#c01420', background: 'rgba(192,20,32,0.08)' }}>{loadError}</p>}
            {!loadError && <p>Aucun participant actif trouvé.</p>}
            <button type="button" className="badge-tool-btn primary" onClick={refreshParticipantsFromApi}>
              Réessayer
            </button>
          </div>
        </section>
      </main>
    );
  }

  return (
    <main className={`badge-studio-page${isExportLocked ? ' badge-studio-page--locked' : ''}`}>
      {exportJob && (
        <ExportJobOverlay
          job={exportJob}
          onCancel={requestCancelExport}
          onDismiss={dismissExportJob}
        />
      )}
      {loadError && (
        <div className="badge-export-status" style={{ maxWidth: 1520, margin: '0 auto 0.75rem', color: '#c01420', background: 'rgba(192,20,32,0.08)' }}>
          {loadError}
        </div>
      )}
      <section className={`badge-studio-shell${isExportLocked ? ' badge-studio-shell--locked' : ''}`}>

        {/* ════════════════ SIDEBAR ════════════════ */}
        <aside className="badge-studio-sidebar">
          <div className="badge-studio-brand">
            <span className="badge-studio-kicker">🎫 Studio badges</span>
            <h1>Génération des badges participants</h1>
            <p>Déplacez les éléments directement sur le badge. Personnalisez polices, couleurs et formes.</p>
          </div>

          <div className="badge-studio-panel">
            <div className="panel-heading">
              <span>Participants</span>
              <strong>{filteredParticipants.length} / {participants.length}</strong>
            </div>
            <button type="button" className="badge-refresh-btn" onClick={refreshParticipantsFromApi} disabled={isLoading || isExportLocked}>
              <i className="bi bi-arrow-clockwise" /> {isLoading ? 'Chargement…' : 'Actualiser'}
            </button>

            {/* Search and Filters */}
            <div className="badge-search-filter-box">
              <div className="search-input-wrapper">
                <i className="bi bi-search search-icon" />
                <input
                  type="text"
                  placeholder="Rechercher par nom..."
                  value={searchQuery}
                  onChange={e => setSearchQuery(e.target.value)}
                  className="badge-search-input"
                />
                {searchQuery && (
                  <button type="button" className="clear-search-btn" onClick={() => setSearchQuery('')}>
                    ✕
                  </button>
                )}
              </div>
              
              <div className="filter-selects-row">
                <select value={selectedRoom} onChange={e => setSelectedRoom(e.target.value)} className="badge-filter-select">
                  <option value="all">Chambres (toutes)</option>
                  {rooms.map(room => (
                    <option key={room} value={room}>Chambre {room}</option>
                  ))}
                </select>
                
                <select value={selectedWorkshop} onChange={e => setSelectedWorkshop(e.target.value)} className="badge-filter-select">
                  <option value="all">Ateliers (tous)</option>
                  {workshops.map(ws => (
                    <option key={ws} value={String(ws)}>Atelier {ws}</option>
                  ))}
                </select>
              </div>
            </div>

            {/* Selection bar */}
            <div className="badge-selection-header">
              <label className="badge-select-all-label">
                <input
                  type="checkbox"
                  checked={allFilteredAreSelected}
                  onChange={toggleSelectAllFiltered}
                  className="badge-checkbox"
                />
                <span>Tout sélectionner</span>
              </label>
              
              {selectedParticipantIds.length > 0 && (
                <button
                  type="button"
                  className="badge-clear-selection-btn"
                  onClick={() => setSelectedParticipantIds([])}
                >
                  Effacer ({selectedParticipantIds.length})
                </button>
              )}
            </div>

            {/* Bulk Download Button */}
            {selectedParticipantIds.length > 0 && (
              <div className="badge-bulk-export-box">
                <label className="badge-export-format-select badge-export-format-select--compact">
                  <span>Format</span>
                  <select
                    value={exportFormat}
                    onChange={event => setExportFormat(event.target.value as ExportFormat)}
                    disabled={isExportLocked}
                  >
                    <option value="png">PNG</option>
                    <option value="pdf">PDF</option>
                  </select>
                </label>
                <button
                  type="button"
                  className="badge-bulk-export-btn"
                  onClick={() => exportSelectedBadges(selectedParticipantIds)}
                  disabled={isExportLocked}
                >
                  <i className="bi bi-download" aria-hidden="true" />
                  Exporter {selectedParticipantIds.length} badge(s)
                </button>
                <button
                  type="button"
                  className="badge-tool-btn badge-bulk-print-btn"
                  onClick={() => printSelectedBadges(selectedParticipantIds)}
                  disabled={isExportLocked}
                >
                  <i className="bi bi-printer" aria-hidden="true" />
                  Imprimer {selectedParticipantIds.length} badge(s)
                </button>
              </div>
            )}

            {/* Participant List */}
            <div className="participant-list">
              {filteredParticipants.map(participant => {
                const isSelected = selectedParticipantIds.includes(participant.id);
                return (
                  <div
                    key={participant.id}
                    className={`participant-row-wrapper${participant.id === activeParticipant?.id ? ' active' : ''}`}
                    onClick={() => selectParticipant(participant.id)}
                  >
                    <input
                      type="checkbox"
                      checked={isSelected}
                      onClick={e => e.stopPropagation()} // Stop selection checkbox from activating row details preview
                      onChange={() => {
                        setSelectedParticipantIds(prev =>
                          prev.includes(participant.id)
                            ? prev.filter(item => item !== participant.id)
                            : [...prev, participant.id]
                        );
                      }}
                      className="badge-checkbox row-checkbox"
                    />
                    
                    <div className="participant-row-content">
                      <span className="participant-avatar" style={{ background: participant.photoColor }}>
                        {participant.photoDataURL
                          ? <img src={participant.photoDataURL} alt="" />
                          : getInitials(participant)
                        }
                      </span>
                      <span>
                        <strong>{participant.prenom} {participant.nom}</strong>
                        <small>
                          Chambre {participant.chambre} · Atelier {participant.atelier}
                          {participant.source ? ` · ${participant.source}` : ''}
                          {participantLayouts[participant.id] ? ' · ✦ Ajusté' : ''}
                        </small>
                      </span>
                    </div>
                  </div>
                );
              })}

              {filteredParticipants.length === 0 && (
                <div className="no-participants-found">
                  <i className="bi bi-person-x" />
                  <p>Aucun participant ne correspond aux critères.</p>
                </div>
              )}
            </div>
          </div>
        </aside>

        {/* ════════════════ WORKSPACE ════════════════ */}
        <section className="badge-studio-workspace">
          <div className="badge-studio-toolbar">
            <div>
              <span>Prévisualisation</span>
              <strong>{activeParticipant.prenom} {activeParticipant.nom}</strong>
            </div>
            <div className="badge-toolbar-actions">
              <button type="button" className="badge-tool-btn" onClick={resetGlobalLayout} disabled={isExportLocked}>
                <i className="bi bi-arrow-counterclockwise" /> Réinitialiser
              </button>
              <label className="badge-export-format-select">
                <span className="sr-only-only">Format</span>
                <select
                  value={exportFormat}
                  onChange={event => setExportFormat(event.target.value as ExportFormat)}
                  disabled={isExportLocked}
                >
                  <option value="png">PNG</option>
                  <option value="pdf">PDF</option>
                </select>
              </label>
              <button
                type="button"
                className="badge-tool-btn"
                onClick={printCurrentBadge}
                disabled={isExportLocked || isPreviewLoading}
              >
                <i className="bi bi-printer" aria-hidden="true" />
                Imprimer
              </button>
              <button
                type="button"
                className="badge-tool-btn primary"
                onClick={exportCurrentBadge}
                disabled={isExportLocked || isPreviewLoading}
              >
                <i className="bi bi-download" aria-hidden="true" />
                Exporter {exportFormat.toUpperCase()}
              </button>
            </div>
          </div>

          <div className="badge-editor-grid">
            {/* ──── Preview ──── */}
            <div className={`badge-preview-frame${isPreviewLoading ? ' is-preview-loading' : ''}`}>
              <div
                ref={badgeRef}
                className="badge-template"
                style={{
                  width: `${zoom}%`,
                  maxWidth: '920px',
                  margin: '0 auto',
                }}
              >
                {/* Dynamic responsive background image */}
                <img
                  src={templateImage}
                  alt="Badge Template"
                  className="badge-template-bg-image"
                  crossOrigin="anonymous"
                  style={{
                    width: '100%',
                    height: 'auto',
                    display: 'block',
                    pointerEvents: 'none',
                    userSelect: 'none',
                  }}
                />

                {/* Coordinate indicator */}
                {draggingElement && dragCoords && (
                  <div className="badge-coords-indicator">
                    X: {dragCoords.x.toFixed(1)}% &nbsp; Y: {dragCoords.y.toFixed(1)}%
                  </div>
                )}

                {!showBadgeLayers ? (
                  <>
                    <BadgeLayerSkeleton layout={effectiveLayout.photo} badgeWidth={badgeWidth} variant="photo" photoShape={photoShape} />
                    <BadgeLayerSkeleton layout={effectiveLayout.name} badgeWidth={badgeWidth} variant="text" />
                    <BadgeLayerSkeleton layout={effectiveLayout.atelier} badgeWidth={badgeWidth} variant="number" />
                    <BadgeLayerSkeleton layout={effectiveLayout.chambre} badgeWidth={badgeWidth} variant="number" />
                  </>
                ) : (
                  <>
                    <div
                      className={`badge-layer badge-photo-layer photo-shape-${photoShape}${selectedElement === 'photo' ? ' selected' : ''}`}
                      style={itemStyle(effectiveLayout.photo, badgeWidth)}
                      onPointerDown={event => handlePointerDown(event, 'photo')}
                      onPointerMove={handlePointerMove}
                      onPointerUp={handlePointerUp}
                    >
                      <span className="generated-photo" style={{ background: activeParticipant.photoColor }}>
                        {activeParticipant.photoDataURL
                          ? <img src={activeParticipant.photoDataURL} alt={`Photo de ${activeParticipant.prenom}`} style={{ pointerEvents: 'none' }} />
                          : getInitials(activeParticipant)
                        }
                      </span>
                      {selectedElement === 'photo' && (
                        <div
                          className="badge-resize-handle bottom-right"
                          onPointerDown={event => handleResizeStart(event, 'photo')}
                          onPointerMove={handleResizeMove}
                          onPointerUp={handleResizeUp}
                        />
                      )}
                    </div>

                    <div
                      className={`badge-layer badge-name-layer${selectedElement === 'name' ? ' selected' : ''}`}
                      style={{ ...itemStyle(effectiveLayout.name, badgeWidth), fontFamily: nameFont, color: nameColor }}
                      onPointerDown={event => handlePointerDown(event, 'name')}
                      onPointerMove={handlePointerMove}
                      onPointerUp={handlePointerUp}
                    >
                      {activeParticipant.prenom} {activeParticipant.nom}
                      {selectedElement === 'name' && (
                        <div
                          className="badge-resize-handle bottom-right"
                          onPointerDown={event => handleResizeStart(event, 'name')}
                          onPointerMove={handleResizeMove}
                          onPointerUp={handleResizeUp}
                        />
                      )}
                    </div>

                    <div
                      className={`badge-layer badge-number-layer${selectedElement === 'atelier' ? ' selected' : ''}`}
                      style={{ ...itemStyle(effectiveLayout.atelier, badgeWidth), color: numberColor }}
                      onPointerDown={event => handlePointerDown(event, 'atelier')}
                      onPointerMove={handlePointerMove}
                      onPointerUp={handlePointerUp}
                    >
                      {activeParticipant.atelier}
                      {selectedElement === 'atelier' && (
                        <div
                          className="badge-resize-handle bottom-right"
                          onPointerDown={event => handleResizeStart(event, 'atelier')}
                          onPointerMove={handleResizeMove}
                          onPointerUp={handleResizeUp}
                        />
                      )}
                    </div>

                    <div
                      className={`badge-layer badge-number-layer${selectedElement === 'chambre' ? ' selected' : ''}`}
                      style={{ ...itemStyle(effectiveLayout.chambre, badgeWidth), color: numberColor }}
                      onPointerDown={event => handlePointerDown(event, 'chambre')}
                      onPointerMove={handlePointerMove}
                      onPointerUp={handlePointerUp}
                    >
                      {activeParticipant.chambre}
                      {selectedElement === 'chambre' && (
                        <div
                          className="badge-resize-handle bottom-right"
                          onPointerDown={event => handleResizeStart(event, 'chambre')}
                          onPointerMove={handleResizeMove}
                          onPointerUp={handleResizeUp}
                        />
                      )}
                    </div>
                  </>
                )}
              </div>

              {/* Zoom controls */}
              <div className="badge-zoom-controls">
                <button type="button" className="badge-zoom-btn" onClick={zoomOut} title="Zoom arrière">−</button>
                <button type="button" className="badge-zoom-btn" onClick={zoomReset} title="Réinitialiser zoom">
                  <span className="badge-zoom-label">{zoom}%</span>
                </button>
                <button type="button" className="badge-zoom-btn" onClick={zoomIn} title="Zoom avant">+</button>
              </div>
            </div>

            {/* ──── Controls ──── */}
            <aside className="badge-controls">

              {/* Scope */}
              <div className="badge-studio-panel">
                <div className="panel-heading">
                  <span>Portée</span>
                </div>
                <div className="scope-switch">
                  <button
                    type="button"
                    className={editScope === 'global' ? 'active' : ''}
                    onClick={() => setEditScope('global')}
                  >
                    ◎ Global
                  </button>
                  <button
                    type="button"
                    className={editScope === 'participant' ? 'active' : ''}
                    onClick={() => setEditScope('participant')}
                  >
                    ◉ Individuel
                  </button>
                </div>
                <p className="scope-help">
                  {editScope === 'global'
                    ? "Les changements s'appliquent à tous les participants."
                    : `Uniquement pour ${activeParticipant.prenom} ${activeParticipant.nom}.`
                  }
                </p>
                {hasCustomLayout && (
                  <button type="button" className="badge-clear-custom-btn" onClick={resetParticipantLayout}>
                    ✕ Retirer les ajustements
                  </button>
                )}
              </div>

              {/* Element selector */}
              <div className="badge-studio-panel">
                <div className="panel-heading">
                  <span>Élément</span>
                </div>
                <div className="element-tabs">
                  {(Object.keys(ELEMENT_LABELS) as BadgeElement[]).map(element => (
                    <button
                      key={element}
                      type="button"
                      className={selectedElement === element ? 'active' : ''}
                      onClick={() => setSelectedElement(element)}
                    >
                      {ELEMENT_ICONS[element]} {ELEMENT_LABELS[element]}
                    </button>
                  ))}
                </div>
              </div>

              {/* Appearance */}
              <div className="badge-studio-panel">
                <div className="panel-heading">
                  <span>Apparence</span>
                </div>

                <label className="badge-select-control">
                  <span>Police du nom</span>
                  <select value={nameFont} onChange={event => setNameFont(event.target.value)}>
                    {FONT_OPTIONS.map(font => (
                      <option key={font.value} value={font.value}>{font.label}</option>
                    ))}
                  </select>
                </label>

                <div className="badge-photo-shape-block">
                  <span className="badge-control-section-title">Forme de la photo</span>
                  <div className="photo-shape-switch">
                    {PHOTO_SHAPES.map(shape => (
                      <button
                        key={shape.value}
                        type="button"
                        className={photoShape === shape.value ? 'active' : ''}
                        onClick={() => setPhotoShape(shape.value)}
                      >
                        {shape.label}
                      </button>
                    ))}
                  </div>
                </div>

                {/* Text color selector */}
                <div className="badge-color-row">
                  <span>Couleur nom</span>
                  <div className="badge-color-swatches">
                    {TEXT_COLORS.map(color => (
                      <button
                        key={color.value}
                        type="button"
                        className={`badge-color-swatch${nameColor === color.value ? ' active' : ''}`}
                        style={{ background: color.value, border: color.value === '#ffffff' ? '2px solid rgba(255,255,255,0.3)' : undefined }}
                        title={color.label}
                        onClick={() => setNameColor(color.value)}
                      />
                    ))}
                  </div>
                </div>

                <div className="badge-color-row">
                  <span>Couleur n°</span>
                  <div className="badge-color-swatches">
                    {TEXT_COLORS.map(color => (
                      <button
                        key={color.value}
                        type="button"
                        className={`badge-color-swatch${numberColor === color.value ? ' active' : ''}`}
                        style={{ background: color.value, border: color.value === '#ffffff' ? '2px solid rgba(255,255,255,0.3)' : undefined }}
                        title={color.label}
                        onClick={() => setNumberColor(color.value)}
                      />
                    ))}
                  </div>
                </div>

                <input
                  ref={templateInputRef}
                  type="file"
                  accept="image/*"
                  className="sr-only-file"
                  onChange={handleTemplateUpload}
                />
                <div className="template-actions">
                  <button type="button" className="badge-tool-btn" onClick={() => templateInputRef.current?.click()}>
                    <i className="bi bi-image" /> Modèle
                  </button>
                  <button type="button" className="badge-tool-btn" onClick={() => setTemplateImage(templateUrl)}>
                    <i className="bi bi-arrow-left-circle" /> Défaut
                  </button>
                </div>
              </div>

              {/* Position & size */}
              <div className="badge-studio-panel">
                <div className="panel-heading">
                  <span>Position & Alignement</span>
                  <strong>{ELEMENT_ICONS[selectedElement]} {ELEMENT_LABELS[selectedElement]}</strong>
                </div>

                {/* Quick Alignment Grid */}
                <div style={{ marginBottom: '1rem' }}>
                  <span className="badge-control-section-title">Alignement rapide</span>
                  <div className="badge-align-grid">
                    <button type="button" className="badge-align-btn" title="Haut Gauche" onClick={() => alignPreset('top-left')}>↖ Haut-G</button>
                    <button type="button" className="badge-align-btn" title="Centrer Haut" onClick={() => alignPreset('top-center')}>⬆ Centrer H</button>
                    <button type="button" className="badge-align-btn" title="Haut Droite" onClick={() => alignPreset('top-right')}>↗ Haut-D</button>
                    <button type="button" className="badge-align-btn" title="Gauche" onClick={() => alignPreset('left')}>⬅ Gauche</button>
                    <button type="button" className="badge-align-btn primary" title="Centrer" onClick={() => alignPreset('center')}>🎯 Centrer</button>
                    <button type="button" className="badge-align-btn" title="Droite" onClick={() => alignPreset('right')}>➡ Droite</button>
                    <button type="button" className="badge-align-btn" title="Bas Gauche" onClick={() => alignPreset('bottom-left')}>↙ Bas-G</button>
                    <button type="button" className="badge-align-btn" title="Centrer Bas" onClick={() => alignPreset('bottom-center')}>⬇ Centrer B</button>
                    <button type="button" className="badge-align-btn" title="Bas Droite" onClick={() => alignPreset('bottom-right')}>↘ Bas-D</button>
                  </div>
                </div>

                {/* Simplified size controls */}
                <div style={{ display: 'grid', gap: '0.8rem', marginBottom: '0.8rem' }}>
                  {/* Font size control (only for text elements) */}
                  {selectedElement !== 'photo' && (
                    <label className="range-control">
                      <span>Taille texte</span>
                      <input
                        type="range"
                        min="14"
                        max="72"
                        step="1"
                        value={selectedLayout.font}
                        onChange={event => updateLayout(selectedElement, { font: Number(event.target.value) })}
                      />
                      <em>{selectedLayout.font}px</em>
                    </label>
                  )}

                  {/* Width control (for all elements, named appropriately) */}
                  <label className="range-control">
                    <span>{selectedElement === 'photo' ? 'Largeur image' : 'Largeur boîte'}</span>
                    <input
                      type="range"
                      min="5"
                      max="90"
                      step="0.1"
                      value={selectedLayout.w}
                      onChange={event => updateLayout(selectedElement, { w: Number(event.target.value) })}
                    />
                    <em>{selectedLayout.w.toFixed(0)}%</em>
                  </label>

                  {/* Height control (only for photo element) */}
                  {selectedElement === 'photo' && (
                    <label className="range-control">
                      <span>Hauteur image</span>
                      <input
                        type="range"
                        min="3"
                        max="60"
                        step="0.1"
                        value={selectedLayout.h}
                        onChange={event => updateLayout(selectedElement, { h: Number(event.target.value) })}
                      />
                      <em>{selectedLayout.h.toFixed(0)}%</em>
                    </label>
                  )}
                </div>

                {/* Advanced manual coordinates */}
                <details className="badge-advanced-details">
                  <summary className="badge-advanced-summary">
                    <span>Ajustements de précision (avancé)</span>
                  </summary>
                  <div style={{ paddingTop: '0.65rem', display: 'grid', gap: '0.65rem' }}>
                    <p style={{ fontSize: '0.72rem', color: 'var(--bs-text-3)', margin: '0 0 0.2rem' }}>
                      Positionnez précisément en pourcentage de la taille globale du badge :
                    </p>
                    <label className="range-control">
                      <span>Position X</span>
                      <input
                        type="range"
                        min="0"
                        max="95"
                        step="0.1"
                        value={selectedLayout.x}
                        onChange={event => updateLayout(selectedElement, { x: Number(event.target.value) })}
                      />
                      <em>{selectedLayout.x.toFixed(1)}%</em>
                    </label>
                    <label className="range-control">
                      <span>Position Y</span>
                      <input
                        type="range"
                        min="0"
                        max="95"
                        step="0.1"
                        value={selectedLayout.y}
                        onChange={event => updateLayout(selectedElement, { y: Number(event.target.value) })}
                      />
                      <em>{selectedLayout.y.toFixed(1)}%</em>
                    </label>
                    {selectedElement !== 'photo' && (
                      <label className="range-control">
                        <span>Hauteur boîte</span>
                        <input
                          type="range"
                          min="3"
                          max="45"
                          step="0.1"
                          value={selectedLayout.h}
                          onChange={event => updateLayout(selectedElement, { h: Number(event.target.value) })}
                        />
                        <em>{selectedLayout.h.toFixed(1)}%</em>
                      </label>
                    )}
                  </div>
                </details>
              </div>

              {/* Assignment info */}
              <div className="badge-studio-panel assignment-panel">
                <div>
                  <span>Affectation</span>
                  <strong>Chambre {activeParticipant.chambre} · Atelier {activeParticipant.atelier}</strong>
                </div>
                <p>Chambre et atelier issus des affectations enregistrées dans l&apos;administration.</p>
              </div>

            </aside>
          </div>
        </section>
      </section>
    </main>
  );
}
