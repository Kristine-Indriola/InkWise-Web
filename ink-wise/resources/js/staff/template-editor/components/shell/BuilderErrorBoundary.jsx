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

    if (typeof window !== 'undefined') {
      window.__INKWISE_LAST_ERROR__ = {
        message: error?.message,
        stack: error?.stack,
        componentStack: info?.componentStack,
        templateId: this.props.templateId ?? null,
      };
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

    return (
      <div className="builder-error-boundary" role="alert" aria-live="assertive">
        <div className="builder-error-boundary__card">
          <h1>Template builder hit a snag</h1>
          <p className="builder-error-boundary__message">{error.message || 'Unknown runtime error.'}</p>
          {info?.componentStack && (
            <details className="builder-error-boundary__details">
              <summary>Show technical details</summary>
              <pre>{info.componentStack}</pre>
            </details>
          )}
          <button type="button" className="builder-error-boundary__action" onClick={this.handleReset}>
            Reload editor
          </button>
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
