import React from 'react';
import PropTypes from 'prop-types';

export class BuilderErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      error: null,
      info: null,
    };
  }

  static getDerivedStateFromError(error) {
    return { error };
  }

  componentDidCatch(error, info) {
    // Surface the error to the console for easier debugging
    /* eslint-disable no-console */
    console.error('[InkWise Builder] A runtime error occurred.', error, info);
    /* eslint-enable no-console */
    this.setState({ info });

    // Save a structured copy to the window for quick interactive inspection
    if (typeof window !== 'undefined') {
      window.__INKWISE_LAST_ERROR__ = {
        message: error?.message,
        stack: error?.stack,
        componentStack: info?.componentStack,
        templateId: this.props.templateId ?? null,
        time: new Date().toISOString(),
      };

      // Helpful developer hint for minified React errors
      if (typeof error?.message === 'string' && error.message.indexOf('Minified React error #') !== -1) {
        console.error('[InkWise Builder] Detected a minified React error. Run `window.__INKWISE_LAST_ERROR__` in the console to see saved details.');
        console.error('[InkWise Builder] For more information, visit: https://reactjs.org/docs/error-decoder.html');
      }
    }
  }

  handleReset = () => {
    const { onReset } = this.props;
    this.setState({ error: null, info: null }, () => {
      if (typeof onReset === 'function') {
        onReset();
      }
    });
  };

  render() {
    const { error, info } = this.state;
    const { children } = this.props;

    if (!error) {
      return children;
    }

    const copyDebugInfo = async () => {
      const payload = (typeof window !== 'undefined' && window.__INKWISE_LAST_ERROR__) ? window.__INKWISE_LAST_ERROR__ : {
        message: error?.message || null,
        stack: error?.stack || null,
        componentStack: info?.componentStack || null,
      };

      const text = JSON.stringify(payload, null, 2);

      // Try Clipboard API first
      if (typeof navigator !== 'undefined' && navigator.clipboard && navigator.clipboard.writeText) {
        try {
          await navigator.clipboard.writeText(text);
          // eslint-disable-next-line no-alert
          alert('Debug info copied to clipboard. Paste it into a bug report or the console.');
          return;
        } catch (e) {
          // fall through to fallback
        }
      }

      // Fallback: log to console and open inspector
      // eslint-disable-next-line no-console
      console.log('[InkWise Builder] Debug info:', payload);
      // eslint-disable-next-line no-alert
      alert('Could not copy to clipboard automatically. Debug info has been logged to the console as `window.__INKWISE_LAST_ERROR__`.');
    };

    return (
      <div className="builder-error-boundary" role="alert" aria-live="assertive">
        <div className="builder-error-boundary__card">
          <h1>Template builder hit a snag</h1>
          <p className="builder-error-boundary__message">{error.message || 'Unknown runtime error.'}</p>
          <p style={{ marginTop: 8, color: '#374151' }}>
            Open the browser console and run <code>window.__INKWISE_LAST_ERROR__</code> for a structured dump of the error (stack, component stack, template id).
          </p>
          <div style={{ display: 'flex', gap: 8, marginTop: 12 }}>
            <button type="button" className="builder-error-boundary__action" onClick={this.handleReset}>
              Reload editor
            </button>
            <button type="button" className="builder-error-boundary__action" onClick={copyDebugInfo}>
              Copy debug info
            </button>
          </div>
          {info?.componentStack && (
            <details className="builder-error-boundary__details" style={{ marginTop: 12 }}>
              <summary>Show technical details</summary>
              <pre>{info.componentStack}</pre>
            </details>
          )}
        </div>
      </div>
    );
  }
}

BuilderErrorBoundary.propTypes = {
  children: PropTypes.node.isRequired,
  onReset: PropTypes.func,
  templateId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
};

BuilderErrorBoundary.defaultProps = {
  onReset: undefined,
  templateId: null,
};
