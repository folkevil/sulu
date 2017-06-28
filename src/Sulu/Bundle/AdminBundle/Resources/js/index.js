// @flow
import React from 'react';
import {render} from 'react-dom';
import {useStrict} from 'mobx';
import createHistory from 'history/createHashHistory';
import Application from './containers/Application';
import {viewStore} from './containers/ViewRenderer';
import Router, {routeStore} from './services/Router';

useStrict(true);

viewStore.add('sulu_admin.list', () => (<h1>Hello World!</h1>));

function startApplication() {
    const router = new Router(createHistory());
    render(<Application router={router} />, document.getElementById('application'));
}

fetch('/admin/configuration', {credentials: 'same-origin'})
    .then((response) => response.json())
    .then((json) => routeStore.addCollection(json.routes))
    .then(startApplication);
