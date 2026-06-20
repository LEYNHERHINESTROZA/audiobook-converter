// ===== REFERENCIAS DOM =====
const audioPlayer = document.getElementById('audio-elemento-oculto');
const btnPlayPause = document.getElementById('btn-player-play-pause');
const svgPlay = document.getElementById('svg-play');
const svgPause = document.getElementById('svg-pause');
const timeCurrent = document.getElementById('time-current');
const timeTotal = document.getElementById('time-total');
const progressBg = document.getElementById('player-progress-bg');
const progressFill = document.getElementById('player-progress-fill');
const progressPin = document.getElementById('player-progress-pin');
const playerContainer = document.querySelector('.player-container');
const volumeSlider = document.getElementById('volume-slider');
const btnVolumeIcon = document.getElementById('btn-volume-icon');
const playerSpeedToggle = document.getElementById('btn-player-speed-toggle');

const selectorIdioma = document.getElementById('selector-idioma');
const selectorVoz = document.getElementById('selector-voz');
const sliderVelocidad = document.getElementById('slider-velocidad');
const valVelocidad = document.getElementById('val-velocidad');
const sliderTono = document.getElementById('slider-tono');
const valTono = document.getElementById('val-tono');

const inputTexto = document.getElementById('input-texto');
const contadorCaracteres = document.getElementById('contador-caracteres');

const zonaDrop = document.getElementById('zona-drop');
const inputArchivo = document.getElementById('input-archivo');
const tarjetaArchivo = document.getElementById('tarjeta-archivo');
const nombreArchivoSpan = document.getElementById('nombre-archivo');
const pesoArchivoSpan = document.getElementById('peso-archivo');
const tipoArchivoBadge = document.getElementById('tipo-archivo-badge');

let intervalCargando = null;

// ===== TABS =====
function mostrarTab(nombre) {
    document.querySelectorAll('.contenido-tab').forEach(t => t.classList.remove('activo'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('activo'));

    document.getElementById('tab-' + nombre).classList.add('activo');

    // Activar el botón del tab que fue clicado
    if (event && event.target) {
        const btn = event.target.closest('.tab');
        if (btn) btn.classList.add('activo');
    }

    if (nombre === 'historial')  cargarHistorial();
    if (nombre === 'misaudios')  cargarMisAudios();
}

// ===== DRAG & DROP Y SELECCIÓN DE ARCHIVO =====
['dragenter', 'dragover'].forEach(eventName => {
    zonaDrop.addEventListener(eventName, highlight, false);
});
['dragleave', 'drop'].forEach(eventName => {
    zonaDrop.addEventListener(eventName, unhighlight, false);
});

function highlight(e) {
    e.preventDefault();
    zonaDrop.style.borderColor = 'var(--color-sena-green)';
    zonaDrop.style.backgroundColor = 'rgba(16, 185, 129, 0.05)';
}

function unhighlight(e) {
    e.preventDefault();
    zonaDrop.style.borderColor = '';
    zonaDrop.style.backgroundColor = '';
}

zonaDrop.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    e.preventDefault();
    const dt = e.dataTransfer;
    const files = dt.files;
    if (files.length) {
        inputArchivo.files = files;
        actualizarArchivoPrevisualizacion(files[0]);
    }
}

inputArchivo.addEventListener('change', function () {
    if (this.files.length) {
        actualizarArchivoPrevisualizacion(this.files[0]);
    }
});

function actualizarArchivoPrevisualizacion(file) {
    if (!file) return;
    const nombre = file.name;
    const extension = nombre.split('.').pop().toUpperCase();
    
    let pesoStr = '';
    if (file.size < 1024 * 1024) {
        pesoStr = (file.size / 1024).toFixed(1) + ' KB';
    } else {
        pesoStr = (file.size / (1024 * 1024)).toFixed(1) + ' MB';
    }
    
    nombreArchivoSpan.textContent = nombre;
    pesoArchivoSpan.textContent = pesoStr;
    tipoArchivoBadge.textContent = extension;
    
    tarjetaArchivo.classList.remove('oculto');
}

function removerArchivoSeleccionado(e) {
    if (e) e.stopPropagation();
    inputArchivo.value = '';
    tarjetaArchivo.classList.add('oculto');
    nombreArchivoSpan.textContent = '';
}

// ===== TEXTAREA CARACTERES =====
function actualizarContadorCaracteres() {
    const len = inputTexto.value.length;
    contadorCaracteres.textContent = len;
    if (len > 5000) {
        contadorCaracteres.style.color = '#ef4444';
    } else {
        contadorCaracteres.style.color = '';
    }
}

// ===== CONFIGURACIONES DEL SIDEBAR =====
const vocesPorIdioma = {
    'es-MX': [
        { value: 'female-premium-1', text: 'Helena (Femenina Premium)' },
        { value: 'male-premium-1', text: 'Mateo (Masculino Premium)', selected: true },
        { value: 'neutral-advanced', text: 'Voz Neural Inteligente (ADSO-Bot)' }
    ],
    'es-CO': [
        { value: 'female-premium-1', text: 'Salomé (Femenina Premium)' },
        { value: 'male-premium-1', text: 'Mateo (Masculino Premium)', selected: true },
        { value: 'neutral-advanced', text: 'Voz Neural Inteligente (ADSO-Bot)' }
    ],
    'es-ES': [
        { value: 'female-premium-1', text: 'Elvira (Femenina Premium)' },
        { value: 'male-premium-1', text: 'Alvaro (Masculino Premium)', selected: true },
        { value: 'neutral-advanced', text: 'Voz Neural Inteligente (ADSO-Bot)' }
    ],
    'en-US': [
        { value: 'female-premium-1', text: 'Jenny (Female Premium)', selected: true },
        { value: 'male-premium-1', text: 'Guy (Male Premium)' },
        { value: 'neutral-advanced', text: 'Neural US-Bot' }
    ],
    'pt-BR': [
        { value: 'female-premium-1', text: 'Francisca (Feminina Premium)', selected: true },
        { value: 'male-premium-1', text: 'Antonio (Masculino Premium)' },
        { value: 'neutral-advanced', text: 'Neural BR-Bot' }
    ]
};

function actualizarVocesDisponibles() {
    const idioma = selectorIdioma.value;
    const voces = vocesPorIdioma[idioma] || [];
    selectorVoz.innerHTML = '';
    voces.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v.value;
        opt.textContent = v.text;
        if (v.selected) opt.selected = true;
        selectorVoz.appendChild(opt);
    });
}

function cambiarVelocidad(val) {
    valVelocidad.textContent = parseFloat(val).toFixed(1) + 'x';
    // Si hay un audio cargado y reproduciéndose, ajustar su velocidad
    if (audioPlayer.src) {
        audioPlayer.playbackRate = parseFloat(val);
        playerSpeedToggle.textContent = audioPlayer.playbackRate.toFixed(1) + 'x';
    }
}

function cambiarTono(val) {
    let tonoTexto = 'Natural';
    const tonoVal = parseInt(val);
    if (tonoVal > 0) tonoTexto = '+' + tonoVal;
    else if (tonoVal < 0) tonoTexto = tonoVal;
    valTono.textContent = tonoTexto;
}

// ===== ESCUCHAR MUESTRA =====
function probarVozSeleccionada() {
    const btn = document.querySelector('.btn-sidebar-preview');
    const textoMuestra = "Hola, esta es una prueba de la voz neuronal del Conversor de Audiolibros ADSO.";
    
    const formData = new FormData();
    formData.append('texto', textoMuestra);
    formData.append('idioma', selectorIdioma.value);
    formData.append('voz', selectorVoz.value);
    formData.append('velocidad', sliderVelocidad.value);
    formData.append('tono', sliderTono.value);
    
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="preview-icon">🔄</span> Generando...';
    
    fetch('http://localhost/audiobook-converter/backend/subir_archivo.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (data.exito) {
            const audioMuestra = new Audio('http://localhost/audiobook-converter/outputs/' + data.archivo_audio);
            audioMuestra.playbackRate = parseFloat(sliderVelocidad.value);
            audioMuestra.play();
        } else {
            alert('Error al generar muestra: ' + data.mensaje);
        }
    })
    .catch((err) => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        console.error('Error de conexión (muestra):', err);
        alert('Error de conexión al generar muestra. Verifica que XAMPP (Apache + MySQL) estén activos.');
    });
}

// ===== CONVERTIR ARCHIVO =====
function convertirArchivo() {
    const archivo = inputArchivo.files[0];
    if (!archivo) {
        alert('Por favor selecciona un archivo PDF o Word.');
        return;
    }

    const formData = new FormData();
    formData.append('archivo', archivo);
    formData.append('idioma', selectorIdioma.value);
    formData.append('voz', selectorVoz.value);
    formData.append('velocidad', sliderVelocidad.value);
    formData.append('tono', sliderTono.value);

    enviarConversion(formData);
}

// ===== CONVERTIR TEXTO =====
function convertirTexto() {
    const texto = inputTexto.value.trim();
    if (!texto) {
        alert('Por favor escribe o pega algún texto.');
        return;
    }
    if (texto.length > 5000) {
        alert('El texto no puede superar los 5,000 caracteres.');
        return;
    }

    const formData = new FormData();
    formData.append('texto', texto);
    formData.append('idioma', selectorIdioma.value);
    formData.append('voz', selectorVoz.value);
    formData.append('velocidad', sliderVelocidad.value);
    formData.append('tono', sliderTono.value);

    enviarConversion(formData);
}

// ===== ENVIAR AL SERVIDOR =====
function enviarConversion(formData) {
    mostrarCargando(true);
    ocultarResultado();

    fetch('http://localhost/audiobook-converter/backend/subir_archivo.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status + ': ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            mostrarCargando(false);
            if (data.exito) {
                mostrarResultado(data.archivo_audio);
            } else {
                alert('Error del servidor: ' + data.mensaje);
            }
        })
        .catch(error => {
            mostrarCargando(false);
            console.error('Error de conexión:', error);
            alert('Error de conexión con el servidor. Verifica que XAMPP (Apache + MySQL) estén activos.');
        });
}

// ===== CARGAR HISTORIAL =====
function cargarHistorial() {
    fetch('http://localhost/audiobook-converter/backend/historial.php')
        .then(response => response.json())
        .then(data => {
            const contenedor = document.getElementById('lista-historial');
            if (data.length === 0) {
                contenedor.innerHTML = '<p>No hay conversiones aún.</p>';
                return;
            }

            let tabla = `<div class="historial-table-wrapper">
            <table>
            <tr>
                <th>Archivo</th>
                <th>Tipo</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>`;

            data.forEach(item => {
                tabla += `<tr>
                <td>📄 ${item.nombre_archivo}</td>
                <td><span class="file-format-badge">${item.tipo_archivo.toUpperCase()}</span></td>
                <td>${item.fecha}</td>
                <td><span class="status-badge success">✅ ${item.estado}</span></td>
                <td>
                    <button class="btn-tabla-escuchar" onclick="cargarAudioEnReproductor('${item.nombre_audio}', '${item.nombre_archivo}')">
                        🎧 Escuchar
                    </button>
                </td>
            </tr>`;
            });

            tabla += '</table></div>';
            contenedor.innerHTML = tabla;
        })
        .catch(() => {
            document.getElementById('lista-historial').innerHTML = '<p>Error al cargar historial.</p>';
        });
}

// ===== PLAYER LOGIC =====
function togglePlayPause() {
    if (!audioPlayer.src) return;
    if (audioPlayer.paused) {
        audioPlayer.play();
    } else {
        audioPlayer.pause();
    }
}

audioPlayer.addEventListener('play', () => {
    playerContainer.classList.add('playing');
    svgPlay.classList.add('oculto');
    svgPause.classList.remove('oculto');
});

audioPlayer.addEventListener('pause', () => {
    playerContainer.classList.remove('playing');
    svgPlay.classList.remove('oculto');
    svgPause.classList.add('oculto');
});

audioPlayer.addEventListener('loadedmetadata', () => {
    timeTotal.textContent = formatTime(audioPlayer.duration);
    timeCurrent.textContent = formatTime(0);
    progressFill.style.width = '0%';
    progressPin.style.left = '0%';
    audioPlayer.playbackRate = parseFloat(sliderVelocidad.value);
    playerSpeedToggle.textContent = audioPlayer.playbackRate.toFixed(1) + 'x';
});

audioPlayer.addEventListener('timeupdate', () => {
    if (audioPlayer.duration) {
        const pct = (audioPlayer.currentTime / audioPlayer.duration) * 100;
        progressFill.style.width = pct + '%';
        progressPin.style.left = pct + '%';
        timeCurrent.textContent = formatTime(audioPlayer.currentTime);
    }
});

function formatTime(secs) {
    if (isNaN(secs)) return '00:00';
    const m = Math.floor(secs / 60).toString().padStart(2, '0');
    const s = Math.floor(secs % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
}

// Buscar en la línea de tiempo
progressBg.addEventListener('click', (e) => {
    const rect = progressBg.getBoundingClientRect();
    const pct = (e.clientX - rect.left) / rect.width;
    if (audioPlayer.duration) {
        audioPlayer.currentTime = pct * audioPlayer.duration;
    }
});

// Control de Velocidad del Player
const speedCycle = [1.0, 1.25, 1.5, 2.0, 0.75];
function togglePlayerSpeed() {
    let currentSpeed = audioPlayer.playbackRate;
    let index = speedCycle.indexOf(currentSpeed);
    if (index === -1) index = 0;
    let nextIndex = (index + 1) % speedCycle.length;
    let nextSpeed = speedCycle[nextIndex];
    audioPlayer.playbackRate = nextSpeed;
    playerSpeedToggle.textContent = nextSpeed.toFixed(2).replace('.00', '') + 'x';
}

// Volumen y Mute
function toggleMute() {
    audioPlayer.muted = !audioPlayer.muted;
    if (audioPlayer.muted) {
        btnVolumeIcon.textContent = '🔇';
        volumeSlider.value = 0;
    } else {
        btnVolumeIcon.textContent = '🔊';
        volumeSlider.value = audioPlayer.volume;
    }
}

function cambiarVolumenPlayer(val) {
    audioPlayer.volume = parseFloat(val);
    audioPlayer.muted = false;
    if (audioPlayer.volume === 0) {
        btnVolumeIcon.textContent = '🔇';
    } else if (audioPlayer.volume < 0.5) {
        btnVolumeIcon.textContent = '🔉';
    } else {
        btnVolumeIcon.textContent = '🔊';
    }
}

// Cargar desde Historial
function cargarAudioEnReproductor(nombreAudio, nombreOriginal) {
    audioPlayer.src = 'http://localhost/audiobook-converter/outputs/' + nombreAudio;
    audioPlayer.load();
    document.getElementById('enlace-descarga').href = 'http://localhost/audiobook-converter/backend/descargar.php?archivo=' + nombreAudio;
    
    document.getElementById('player-track-title').textContent = nombreOriginal;
    document.getElementById('player-track-details').textContent = 'Conversión cargada del Historial';
    
    document.getElementById('resultado').classList.remove('oculto');
    document.getElementById('resultado').scrollIntoView({ behavior: 'smooth' });
    
    audioPlayer.play();
}

// ===== HELPERS =====
function mostrarCargando(mostrar) {
    const overlay = document.getElementById('cargando');
    const progressFill = document.getElementById('cargando-bar-progreso');
    const porcentajeText = document.getElementById('cargando-porcentaje-texto');
    const faseText = document.getElementById('cargando-fase-texto');
    
    if (mostrar) {
        overlay.classList.remove('oculto');
        progressFill.style.width = '0%';
        porcentajeText.textContent = '0%';
        faseText.textContent = 'Conectando con el motor TTS de ADSO...';
        
        let pct = 0;
        intervalCargando = setInterval(() => {
            if (pct < 95) {
                pct += Math.floor(Math.random() * 5) + 1;
                if (pct > 95) pct = 95;
                progressFill.style.width = pct + '%';
                porcentajeText.textContent = pct + '%';
                
                if (pct < 30) {
                    faseText.textContent = 'Conectando con el motor TTS de ADSO...';
                } else if (pct < 60) {
                    faseText.textContent = 'Analizando texto y estructura sintáctica...';
                } else if (pct < 85) {
                    faseText.textContent = 'Sintetizando audio neuronal de alta fidelidad...';
                } else {
                    faseText.textContent = 'Empaquetando pista MP3 final...';
                }
            }
        }, 300);
    } else {
        clearInterval(intervalCargando);
        progressFill.style.width = '100%';
        porcentajeText.textContent = '100%';
        faseText.textContent = '¡Listo!';
        setTimeout(() => {
            overlay.classList.add('oculto');
        }, 400);
    }
}

function mostrarResultado(archivoAudio) {
    const resultado = document.getElementById('resultado');
    const enlace = document.getElementById('enlace-descarga');
    
    audioPlayer.src = 'http://localhost/audiobook-converter/outputs/' + archivoAudio;
    audioPlayer.load();
    
    enlace.href = 'http://localhost/audiobook-converter/backend/descargar.php?archivo=' + archivoAudio;
    
    document.getElementById('player-track-title').textContent = 'audiolibro_' + archivoAudio;
    document.getElementById('player-track-details').textContent = 'Voz: ' + selectorVoz.options[selectorVoz.selectedIndex].text + ' · Velocidad: ' + parseFloat(sliderVelocidad.value).toFixed(1) + 'x';
    
    resultado.classList.remove('oculto');
    resultado.scrollIntoView({ behavior: 'smooth' });
}

function ocultarResultado() {
    document.getElementById('resultado').classList.add('oculto');
}

// Inicializar lista de voces al cargar
actualizarVocesDisponibles();

// =============================================================
// ===== MIS AUDIOS — BIBLIOTECA PERSONAL ======================
// =============================================================

const BACKEND_AUDIOS = 'http://localhost/audiobook-converter/backend/gestionar_audios.php';

// Elemento <audio> compartido para el mini-reproductor de tarjetas
let cardAudio = new Audio();
let activeCardId = null; // nombre del archivo actualmente reproducido

// --- Drag & Drop en la zona de audio ---
const zonaAudioDrop = document.getElementById('zona-audio-drop');
const inputAudioPropio = document.getElementById('input-audio-propio');

['dragenter', 'dragover'].forEach(ev => {
    zonaAudioDrop.addEventListener(ev, e => {
        e.preventDefault();
        zonaAudioDrop.style.borderColor = '#8b5cf6';
        zonaAudioDrop.style.backgroundColor = 'rgba(139, 92, 246, 0.05)';
    }, false);
});

['dragleave', 'drop'].forEach(ev => {
    zonaAudioDrop.addEventListener(ev, e => {
        e.preventDefault();
        zonaAudioDrop.style.borderColor = '';
        zonaAudioDrop.style.backgroundColor = '';
    }, false);
});

zonaAudioDrop.addEventListener('drop', e => {
    e.preventDefault();
    const files = e.dataTransfer.files;
    if (files.length) subirAudiosEnSecuencia([...files]);
}, false);

inputAudioPropio.addEventListener('change', function () {
    if (this.files.length) subirAudiosEnSecuencia([...this.files]);
    this.value = '';
});

// --- Subir múltiples archivos en secuencia ---
async function subirAudiosEnSecuencia(archivos) {
    const statusEl = document.getElementById('audio-upload-status');
    const barEl    = document.getElementById('audio-upload-bar');
    const progEl   = document.getElementById('audio-upload-progress');

    progEl.classList.remove('oculto');
    barEl.style.width = '0%';

    for (let i = 0; i < archivos.length; i++) {
        const archivo = archivos[i];
        statusEl.className = 'audio-upload-status';
        statusEl.textContent = `Subiendo ${i + 1}/${archivos.length}: ${archivo.name}…`;

        try {
            await subirUnAudio(archivo, barEl);
        } catch (err) {
            statusEl.className = 'audio-upload-status err';
            statusEl.textContent = `Error en "${archivo.name}": ${err.message}`;
            await esperar(1800);
        }
    }

    statusEl.className = 'audio-upload-status ok';
    statusEl.textContent = archivos.length > 1
        ? `✓ ${archivos.length} audios subidos correctamente`
        : '✓ Audio subido correctamente';

    barEl.style.width = '100%';

    await esperar(1200);
    progEl.classList.add('oculto');
    statusEl.textContent = '';

    cargarMisAudios(); // refrescar lista
}

function esperar(ms) {
    return new Promise(res => setTimeout(res, ms));
}

// --- Subir un solo audio con XMLHttpRequest (para progreso real) ---
function subirUnAudio(archivo) {
    return new Promise((resolve, reject) => {
        const barEl = document.getElementById('audio-upload-bar');
        const fd = new FormData();
        fd.append('action', 'upload');
        fd.append('audio', archivo);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', BACKEND_AUDIOS);

        xhr.upload.addEventListener('progress', e => {
            if (e.lengthComputable) {
                barEl.style.width = Math.round((e.loaded / e.total) * 100) + '%';
            }
        });

        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.exito) resolve(data);
                    else reject(new Error(data.mensaje));
                } catch {
                    reject(new Error('Respuesta inválida del servidor'));
                }
            } else {
                reject(new Error(`HTTP ${xhr.status}`));
            }
        };

        xhr.onerror = () => reject(new Error('Error de red'));
        xhr.send(fd);
    });
}

// --- Cargar y renderizar la lista de audios ---
function cargarMisAudios() {
    const contenedor = document.getElementById('lista-mis-audios');
    contenedor.innerHTML = '<p class="cargando" style="color:var(--text-muted);font-size:.85rem;text-align:center">Cargando biblioteca…</p>';

    fetch(BACKEND_AUDIOS + '?action=list')
        .then(r => r.json())
        .then(data => {
            if (!data.exito) {
                contenedor.innerHTML = `<p style="color:#f87171;text-align:center">Error: ${data.mensaje}</p>`;
                return;
            }

            if (data.archivos.length === 0) {
                contenedor.innerHTML = `
                    <div class="misaudios-empty">
                        <span class="misaudios-empty-icon">🎵</span>
                        <p>Tu biblioteca está vacía.<br>Sube tu primer archivo de audio usando la zona de arriba.</p>
                    </div>`;
                return;
            }

            contenedor.innerHTML = '';
            data.archivos.forEach(a => {
                contenedor.appendChild(crearTarjetaAudio(a));
            });
        })
        .catch(err => {
            contenedor.innerHTML = `<p style="color:#f87171;text-align:center">Error de conexión: ${err.message}</p>`;
        });
}

// --- Construir la tarjeta HTML de un audio ---
function crearTarjetaAudio(audio) {
    const card = document.createElement('div');
    card.className = 'audio-card';
    card.dataset.nombre = audio.nombre;

    // SVG play / pause
    const svgPlay  = `<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>`;
    const svgPause = `<svg viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>`;

    card.innerHTML = `
        <button class="audio-card-play-btn" id="play-btn-${CSS.escape(audio.nombre)}"
                onclick="toggleCardPlay('${audio.nombre}', '${audio.url}', this)"
                title="Reproducir / Pausar">
            ${svgPlay}
        </button>

        <div class="audio-card-info">
            <div class="audio-card-name" title="${audio.nombre}">${audio.nombre}</div>
            <div class="audio-card-meta">
                <span class="audio-card-ext">${audio.extension}</span>
                <span class="audio-card-size">${audio.tamano}</span>
                <span class="audio-card-date">📅 ${audio.fecha}</span>
            </div>
            <div class="audio-card-progress-wrap">
                <div class="audio-card-progress-bar"
                     id="prog-bar-${CSS.escape(audio.nombre)}"
                     onclick="seekCardAudio(event, this)">
                    <div class="audio-card-progress-fill"
                         id="prog-fill-${CSS.escape(audio.nombre)}"></div>
                </div>
                <span class="audio-card-time" id="time-${CSS.escape(audio.nombre)}">0:00</span>
            </div>
        </div>

        <div class="audio-card-actions">
            <button class="btn-audio-action copy"
                    data-tip="Copiar URL"
                    onclick="copiarUrlAudio('${audio.url}')">🔗</button>
            <a class="btn-audio-action download"
               data-tip="Descargar"
               href="${audio.url}" download="${audio.nombre}" title="Descargar">⬇️</a>
            <button class="btn-audio-action delete"
                    data-tip="Eliminar"
                    onclick="eliminarAudio('${audio.nombre}', this)">🗑️</button>
        </div>`;

    return card;
}

// --- Play / Pause de tarjeta individual ---
function toggleCardPlay(nombre, url, btn) {
    const svgPlay  = `<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>`;
    const svgPause = `<svg viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>`;

    // Si hay otro reproduciéndose, pararlo
    if (activeCardId && activeCardId !== nombre) {
        const prevBtn  = document.getElementById('play-btn-' + CSS.escape(activeCardId));
        const prevFill = document.getElementById('prog-fill-' + CSS.escape(activeCardId));
        const prevTime = document.getElementById('time-' + CSS.escape(activeCardId));
        if (prevBtn) { prevBtn.classList.remove('playing'); prevBtn.innerHTML = svgPlay; }
        if (prevFill) prevFill.style.width = '0%';
        if (prevTime) prevTime.textContent = '0:00';
        cardAudio.pause();
    }

    if (activeCardId === nombre && !cardAudio.paused) {
        // Pausar el actual
        cardAudio.pause();
        btn.classList.remove('playing');
        btn.innerHTML = svgPlay;
        return;
    }

    // Reproducir
    if (cardAudio.src !== url) {
        cardAudio.src = url;
        cardAudio.load();
    }

    activeCardId = nombre;
    btn.classList.add('playing');
    btn.innerHTML = svgPause;
    cardAudio.play().catch(() => {});

    // Actualizar barra de progreso mientras avanza
    cardAudio.ontimeupdate = () => {
        if (!cardAudio.duration) return;
        const pct  = (cardAudio.currentTime / cardAudio.duration) * 100;
        const fill = document.getElementById('prog-fill-' + CSS.escape(nombre));
        const time = document.getElementById('time-' + CSS.escape(nombre));
        if (fill) fill.style.width = pct + '%';
        if (time) time.textContent = formatTime(cardAudio.currentTime);
    };

    cardAudio.onended = () => {
        btn.classList.remove('playing');
        btn.innerHTML = svgPlay;
        activeCardId = null;
        const fill = document.getElementById('prog-fill-' + CSS.escape(nombre));
        const time = document.getElementById('time-' + CSS.escape(nombre));
        if (fill) fill.style.width = '0%';
        if (time) time.textContent = '0:00';
    };
}

// --- Seek al hacer clic en la barra de progreso ---
function seekCardAudio(e, barEl) {
    if (!cardAudio.duration || !activeCardId) return;
    const rect = barEl.getBoundingClientRect();
    const pct  = (e.clientX - rect.left) / rect.width;
    cardAudio.currentTime = pct * cardAudio.duration;
}

// --- Copiar URL al portapapeles ---
function copiarUrlAudio(url) {
    navigator.clipboard.writeText(url)
        .then(() => mostrarToast('🔗 URL copiada al portapapeles'))
        .catch(() => {
            // Fallback para navegadores sin Clipboard API
            const ta = document.createElement('textarea');
            ta.value = url;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            mostrarToast('🔗 URL copiada al portapapeles');
        });
}

// --- Eliminar audio ---
function eliminarAudio(nombre, btn) {
    if (!confirm(`¿Eliminar "${nombre}"?\nEsta acción no se puede deshacer.`)) return;

    btn.disabled = true;
    btn.textContent = '⏳';

    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('nombre', nombre);

    fetch(BACKEND_AUDIOS, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.exito) {
                // Si el eliminado está reproduciéndose, detener
                if (activeCardId === nombre) {
                    cardAudio.pause();
                    activeCardId = null;
                }
                // Animar salida de la tarjeta
                const card = document.querySelector(`.audio-card[data-nombre="${nombre}"]`);
                if (card) {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity   = '0';
                    card.style.transform = 'translateX(20px)';
                    setTimeout(() => { card.remove(); revisarListaVacia(); }, 300);
                }
                mostrarToast('🗑️ Audio eliminado');
            } else {
                alert('Error al eliminar: ' + data.mensaje);
                btn.disabled = false;
                btn.textContent = '🗑️';
            }
        })
        .catch(() => {
            alert('Error de conexión al eliminar.');
            btn.disabled = false;
            btn.textContent = '🗑️';
        });
}

function revisarListaVacia() {
    const cont = document.getElementById('lista-mis-audios');
    if (cont && cont.querySelectorAll('.audio-card').length === 0) {
        cont.innerHTML = `
            <div class="misaudios-empty">
                <span class="misaudios-empty-icon">🎵</span>
                <p>Tu biblioteca está vacía.<br>Sube tu primer archivo de audio usando la zona de arriba.</p>
            </div>`;
    }
}

// --- Toast visual ---
let toastTimeout = null;
function mostrarToast(msg) {
    let toast = document.getElementById('toast-copiado-global');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast-copiado-global';
        toast.className = 'toast-copiado';
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    clearTimeout(toastTimeout);
    // Forzar reflow para reiniciar animación
    toast.classList.remove('visible');
    void toast.offsetWidth;
    toast.classList.add('visible');
    toastTimeout = setTimeout(() => toast.classList.remove('visible'), 2400);
}