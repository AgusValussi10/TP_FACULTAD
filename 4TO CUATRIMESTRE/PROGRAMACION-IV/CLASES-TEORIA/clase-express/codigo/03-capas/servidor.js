'use strict';
const { crearApp } = require('./app');
const app = crearApp({ umbralAlerta: 30, version: '1.0.0' });
app.listen(3000, () => console.log('capas escuchando en :3000'));
