# 🎧 ADSO Audiobook Converter

> **Proyecto Formativo ADSO — SENA Centro de Formación de Actividad Física y Cultura, Bogotá D.C., 2026**

Aplicación web completa para convertir documentos de texto (PDF, DOCX, TXT) y texto plano en audiolibros MP3 mediante síntesis de voz neuronal (TTS), con una biblioteca personal de audios.

---

## ✨ Características

- 📄 **Conversión de archivos** — Sube PDF, DOCX o TXT y convierte a audio MP3
- ✏️ **Texto directo** — Pega cualquier texto y genera el audio al instante
- 🎵 **Biblioteca personal** — Sube, reproduce, copia la URL y descarga tus propios audios
- 🗃️ **Historial MySQL** — Todas las conversiones quedan registradas en base de datos
- 🎛️ **Control de voz** — Elige idioma, modelo de voz, velocidad y tono
- ⚡ **Validación Java** — El validador en Java verifica el archivo antes de convertir
- 🐍 **Motor Python TTS** — Conversión mediante síntesis neuronal de alta fidelidad

---

## 🗂️ Estructura del Proyecto

```
audiobook-converter/
├── backend/
│   ├── conectar.php          # Conexión MySQL
│   ├── subir_archivo.php     # Endpoint principal de conversión
│   ├── historial.php         # Consulta historial de conversiones
│   ├── gestionar_audios.php  # CRUD biblioteca personal de audios
│   ├── descargar.php         # Descarga de archivos generados
│   ├── stats_app.php         # Estadísticas de uso
│   └── analisis_datos.php    # Análisis de datos
├── frontend/
│   ├── index.html            # Interfaz principal (glassmorphism)
│   ├── style.css             # Sistema de diseño cósmico
│   └── app.js                # Lógica del cliente
├── java/
│   └── Validador.java        # Validador de archivos (tamaño/extensión)
├── python/
│   └── convertir.py          # Motor TTS neuronal
├── sql/
│   └── Conexión.SQL          # Script de creación de BD
├── uploads/                  # Archivos subidos (ignorado en git)
└── outputs/                  # Audios generados (ignorado en git)
```

---

## 🚀 Instalación Local (XAMPP)

### Requisitos
- XAMPP (Apache + MySQL) — Puerto 80
- PHP 7.4+
- Java JDK 11+
- Python 3.8+ con librería `edge-tts`

### Pasos

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/TU_USUARIO/audiobook-converter.git
   cd C:/xampp/htdocs/audiobook-converter
   ```

2. **Iniciar XAMPP** — Arrancar Apache y MySQL desde el panel de control.

3. **Crear la base de datos** — Abrir phpMyAdmin o ejecutar:
   ```bash
   mysql -u root -e "SOURCE sql/Conexión.SQL"
   ```
   O manualmente ejecutar el contenido de `sql/Conexión.SQL`.

4. **Instalar dependencia Python:**
   ```bash
   pip install edge-tts
   ```

5. **Compilar el validador Java:**
   ```bash
   cd java
   javac Validador.java
   ```

6. **Abrir la app** en el navegador:
   ```
   http://localhost/audiobook-converter/frontend/index.html
   ```

---

## 🛠️ Tecnologías

| Capa | Tecnología |
|------|-----------|
| Frontend | HTML5, CSS3 (Glassmorphism), JavaScript ES2022 |
| Backend | PHP 8+ con MySQLi |
| Base de datos | MySQL (XAMPP) |
| TTS Engine | Python + edge-tts (Microsoft Neural TTS) |
| Validación | Java 11+ |
| Tipografías | Inter, Outfit, Fira Code (Google Fonts) |

---

## 📌 Notas de Configuración

- Las URLs del backend apuntan a `http://localhost/audiobook-converter/` — no cambiar si usas XAMPP estándar.
- La carpeta `uploads/audios/` debe tener permisos de escritura para PHP.
- El archivo `.gitignore` excluye los audios generados y subidos por usuarios.

---

## 📄 Licencia

Proyecto académico formativo — SENA 2026. Todos los derechos reservados.

---

## ????? Autor

**Leynher Ferney Hinestroza Mosquera**
*Desarrollador Fullstack & Analista de Datos*
- ?? [LinkedIn](https://linkedin.com) (En construcci�n)
- ?? [GitHub](https://github.com/LEYNHERHINESTROZA)
- ?? Email: leynercrs@gmail.com
