import java.io.File;

public class Validador {

    // Tamaño máximo permitido: 10MB
    private static final long TAMANO_MAXIMO = 10 * 1024 * 1024;

    // Extensiones permitidas
    private static final String[] EXTENSIONES_PERMITIDAS = {"pdf", "docx", "txt"};

    public static void main(String[] args) {

        if (args.length != 1) {
            System.out.println("ERROR: Debe proporcionar la ruta del archivo.");
            System.exit(1);
        }

        String rutaArchivo = args[0];
        File archivo = new File(rutaArchivo);

        // Validar que existe
        if (!archivo.exists()) {
            System.out.println("ERROR: El archivo no existe.");
            System.exit(1);
        }

        // Validar tamaño
        if (archivo.length() > TAMANO_MAXIMO) {
            System.out.println("ERROR: El archivo supera el tamaño máximo de 10MB.");
            System.exit(1);
        }

        // Validar extensión
        String extension = obtenerExtension(archivo.getName());
        if (!esExtensionPermitida(extension)) {
            System.out.println("ERROR: Extensión no permitida. Use PDF, DOCX o TXT.");
            System.exit(1);
        }

        // Validar que no esté vacío
        if (archivo.length() == 0) {
            System.out.println("ERROR: El archivo está vacío.");
            System.exit(1);
        }

        System.out.println("OK: Archivo válido.");
        System.exit(0);
    }

    private static String obtenerExtension(String nombreArchivo) {
        int punto = nombreArchivo.lastIndexOf('.');
        if (punto == -1) return "";
        return nombreArchivo.substring(punto + 1).toLowerCase();
    }

    private static boolean esExtensionPermitida(String extension) {
        for (String ext : EXTENSIONES_PERMITIDAS) {
            if (ext.equals(extension)) return true;
        }
        return false;
    }
}