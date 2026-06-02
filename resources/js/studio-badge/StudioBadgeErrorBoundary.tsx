import React, { Component, type ErrorInfo, type ReactNode } from 'react';

interface StudioBadgeErrorBoundaryProps {
  children: ReactNode;
}

interface StudioBadgeErrorBoundaryState {
  message: string;
}

/**
 * Affiche une erreur React lisible au lieu d'une page blanche.
 */
export default class StudioBadgeErrorBoundary extends Component<
  StudioBadgeErrorBoundaryProps,
  StudioBadgeErrorBoundaryState
> {
  constructor(props: StudioBadgeErrorBoundaryProps) {
    super(props);
    this.state = { message: '' };
  }

  static getDerivedStateFromError(error: Error): StudioBadgeErrorBoundaryState {
    return { message: error.message || 'Erreur inattendue dans le studio badges.' };
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    console.error('Studio badges', error, info);
  }

  render(): ReactNode {
    if (this.state.message) {
      return (
        <main className="badge-studio-page">
          <section className="badge-studio-shell" style={{ display: 'block', padding: '2rem' }}>
            <div className="badge-studio-panel">
              <h1 style={{ marginTop: 0 }}>Studio badges</h1>
              <p className="badge-export-status" style={{ color: '#c01420', background: 'rgba(192,20,32,0.08)' }}>
                {this.state.message}
              </p>
              <p style={{ fontSize: '0.85rem', color: '#64748b' }}>
                Rechargez la page. Si le problème persiste, contactez l&apos;équipe technique.
              </p>
            </div>
          </section>
        </main>
      );
    }

    return this.props.children;
  }
}
