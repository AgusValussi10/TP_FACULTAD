# 🎓 TP Facultad

Repositorio donde se junta el trabajo de la carrera (UTN FRRe): trabajos prácticos, integradores y proyectos de las materias del 3er y 4to cuatrimestre, todo en un solo lugar.

## 👥 Trabajo compartido

No es un repo individual: se trabaja en equipo, con más de un colaborador haciendo commits (más de 200 hasta ahora). Cada integrante aporta a distintos proyectos, así que el historial es una mezcla de entregas, correcciones y mejoras hechas entre varios.

## 📁 Cómo está organizado

El repo se ordena por cuatrimestre y, dentro de cada uno, por materia o proyecto:

```
TP_FACULTAD/
├── 3ER CUATRIMESTRE/
└── 4TO CUATRIMESTRE/
```

Cada carpeta de materia tiene su propio código, consignas y, en algunos casos, documentación propia.

## ✨ Lo más interesante

- **Muchas tecnologías en un mismo lugar**: backend en PHP, aplicaciones .NET (C#), JavaScript/Node, bases de datos SQL y un cliente móvil. Pasa por varios stacks distintos.
- **Proyectos que llegan a producción**: algunos están pensados para desplegarse (Docker, Nixpacks, Railway) y usar bases de datos remotas, no se quedan en "funciona en mi máquina".
- **Del código a la entrega completa**: además del software hay documentación, modelos de negocio, diseños de interfaz (Figma) y material de innovación del trabajo final.
- **Testing automatizado**: hay pruebas end-to-end con Cypress y hooks de Git con Husky para mantener cierta disciplina de calidad.
- **Desarrollo asistido por IA**: parte del trabajo se hizo con Claude Code, y varios proyectos incluyen un `CLAUDE.md` con el contexto y la arquitectura del sistema.

## 🚀 Cómo usarlo

```bash
git clone <url-del-repo>
cd TP_FACULTAD
```

Cada proyecto tiene sus propias instrucciones de instalación y ejecución dentro de su carpeta (buscá `CLAUDE.md` o archivos de texto/markdown con indicaciones). Para las dependencias de Node de la raíz (Cypress):

```bash
npm install
```

## 📝 Nota

Es material académico. El código es el que se presentó o se fue armando durante la cursada, así que algunas partes pueden estar en estado de trabajo en progreso.
