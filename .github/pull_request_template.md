## Qué cambia

<!-- Una o dos oraciones: qué hace este PR y por qué. -->

**Requerimiento / HU:** RFG__ · HU-____
**Tipo:** feat / fix / refactor / docs / chore / test

## Cómo probarlo

<!-- Pasos para que el revisor lo verifique: usuario de demo, pantalla y resultado esperado. -->

1.
2.

## Checklist del autor (antes de pedir revisión)

- [ ] El PR resuelve **una sola** HU o tarea y tiene menos de 400 líneas cambiadas
- [ ] El pre-commit pasó (`php -l` + `phpcs`) y no usé `--no-verify`
- [ ] Los checks del pipeline están en verde (Lint, Tests, Build, SonarCloud)
- [ ] Probé el cambio en local con los usuarios de demo
- [ ] Si cambia el comportamiento, actualicé `CLAUDE.md` / la documentación
- [ ] No hay credenciales, contraseñas ni datos personales en el código

## Checklist del revisor (revisión por pares)

- [ ] **Funcionalidad:** cumple los criterios de aceptación de la HU
- [ ] **Seguridad:** valida el rol de la sesión y usa sentencias preparadas (sin SQL concatenado)
- [ ] **Validaciones:** las reglas de negocio se validan en el servidor, no solo en el cliente
- [ ] **Errores:** no hay `catch` vacíos; los errores se registran y se responden con JSON
- [ ] **Transacciones:** si toca varias tablas, usa transacción con `rollback`
- [ ] **Legibilidad:** nombres claros, funciones cortas, comentarios donde hay reglas de negocio
- [ ] **Pruebas:** hay tests para el caso nuevo o se justifica por qué no

<!-- El revisor deja los comentarios en las líneas del código. "Request changes" bloquea el merge hasta que se resuelvan. -->
