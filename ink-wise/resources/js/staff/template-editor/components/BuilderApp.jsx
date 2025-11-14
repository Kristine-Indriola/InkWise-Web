import React from 'react';
import PropTypes from 'prop-types';

import { BuilderShell } from './shell/BuilderShell';
import { BuilderStoreProvider } from '../state/BuilderStore';

export function BuilderApp({ bootstrap }) {
  const template = bootstrap?.template ?? {};
  const routes = bootstrap?.routes ?? {};
  const flags = bootstrap?.flags ?? {};
  const user = bootstrap?.user ?? null;
  const csrfToken = bootstrap?.csrfToken ?? '';

  return (
    <BuilderStoreProvider
      template={template}
      routes={routes}
      flags={flags}
      user={user}
      csrfToken={csrfToken}
    >
      <BuilderShell />
    </BuilderStoreProvider>
  );
}

BuilderApp.propTypes = {
  bootstrap: PropTypes.shape({
    template: PropTypes.object,
    routes: PropTypes.object,
    flags: PropTypes.object,
    user: PropTypes.object,
    csrfToken: PropTypes.string,
  }),
};

BuilderApp.defaultProps = {
  bootstrap: {},
};
