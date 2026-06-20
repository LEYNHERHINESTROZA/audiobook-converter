import sys
import os
import pdfplumber
import argparse
from docx import Document
from gtts import gTTS

def extraer_texto_pdf(ruta):
    """Extrae texto de un archivo PDF"""
    texto = ""
    try:
        with pdfplumber.open(ruta) as pdf:
            for pagina in pdf.pages:
                contenido = pagina.extract_text()
                if contenido:
                    texto += contenido + "\n"
    except Exception as e:
        print(f"Error leyendo PDF: {e}")
    return texto.strip()

def extraer_texto_word(ruta):
    """Extrae texto de un archivo Word (.docx)"""
    texto = ""
    try:
        doc = Document(ruta)
        for parrafo in doc.paragraphs:
            if parrafo.text.strip():
                texto += parrafo.text + "\n"
    except Exception as e:
        print(f"Error leyendo Word: {e}")
    return texto.strip()

def extraer_texto_plano(ruta):
    """Lee un archivo de texto plano"""
    try:
        with open(ruta, 'r', encoding='utf-8') as f:
            return f.read().strip()
    except Exception as e:
        print(f"Error leyendo texto: {e}")
        return ""

def convertir_a_audio(texto, ruta_salida, lang='es-MX', voice='male-premium-1', speed=1.0, pitch=0):
    """Convierte texto a MP3 usando gTTS"""
    try:
        # Limitar texto a 5000 caracteres por limitación de gTTS
        if len(texto) > 5000:
            texto = texto[:5000]

        # Obtener mapeo regional (TLD)
        gtts_lang = 'es'
        gtts_tld = 'com.mx'
        
        if lang == 'es-CO':
            gtts_lang = 'es'
            gtts_tld = 'com.co'
        elif lang == 'es-ES':
            gtts_lang = 'es'
            gtts_tld = 'es'
        elif lang == 'es-MX':
            gtts_lang = 'es'
            gtts_tld = 'com.mx'
        elif lang == 'en-US':
            gtts_lang = 'en'
            gtts_tld = 'com'
        elif lang == 'pt-BR':
            gtts_lang = 'pt'
            gtts_tld = 'com.br'

        # Variación por voz (simulación usando diferentes acentos en español)
        if gtts_lang == 'es':
            if voice == 'female-premium-1': # Helena
                gtts_tld = 'es'
            elif voice == 'male-premium-1': # Mateo
                gtts_tld = 'com.mx'
            elif voice == 'neutral-advanced': # ADSO-Bot
                gtts_tld = 'com.co'

        # Determinar si es lento
        slow = float(speed) < 1.0

        tts = gTTS(text=texto, lang=gtts_lang, tld=gtts_tld, slow=slow)
        tts.save(ruta_salida)
        print(f"Audio guardado en: {ruta_salida} (Idioma: {gtts_lang}, TLD: {gtts_tld}, Lento: {slow})")
        return True
    except Exception as e:
        print(f"Error generando audio: {e}")
        return False

def main():
    parser = argparse.ArgumentParser(description="Conversor de texto a audio del Proyecto ADSO")
    parser.add_argument("ruta_archivo", help="Ruta del archivo de texto, PDF o Word de entrada")
    parser.add_argument("ruta_salida", help="Ruta del archivo MP3 de salida")
    parser.add_argument("--lang", default="es-MX", help="Idioma y acento")
    parser.add_argument("--voice", default="male-premium-1", help="Modelo de voz")
    parser.add_argument("--speed", default="1.0", help="Velocidad de reproducción")
    parser.add_argument("--pitch", default="0", help="Tono / Modulación")

    args = parser.parse_args()

    ruta_archivo = args.ruta_archivo
    ruta_salida = args.ruta_salida

    # Verificar que el archivo existe
    if not os.path.exists(ruta_archivo):
        print(f"Error: No se encontró el archivo {ruta_archivo}")
        sys.exit(1)

    # Determinar tipo de archivo y extraer texto
    extension = os.path.splitext(ruta_archivo)[1].lower()

    if extension == '.pdf':
        texto = extraer_texto_pdf(ruta_archivo)
    elif extension == '.docx':
        texto = extraer_texto_word(ruta_archivo)
    elif extension == '.txt':
        texto = extraer_texto_plano(ruta_archivo)
    else:
        print(f"Formato no soportado: {extension}")
        sys.exit(1)

    # Verificar que se extrajo texto
    if not texto:
        print("Error: No se pudo extraer texto del archivo.")
        sys.exit(1)

    print(f"Texto extraído: {len(texto)} caracteres")

    # Convertir a audio
    exito = convertir_a_audio(
        texto, 
        ruta_salida, 
        lang=args.lang, 
        voice=args.voice, 
        speed=args.speed, 
        pitch=args.pitch
    )

    if exito:
        sys.exit(0)
    else:
        sys.exit(1)

if __name__ == '__main__':
    main()