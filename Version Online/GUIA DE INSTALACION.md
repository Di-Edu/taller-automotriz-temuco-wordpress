# Instalar la app de Patentes (gratis, desde cualquier celular)

La app guarda y lee los datos directamente en tu **Google Sheet**. No hay servidor ni pago mensual: solo usa tu cuenta de Google.
Tiempo: unos 10 minutos.

---

## 1. Abrir la planilla

Abre la planilla de **respuestas de tu Google Form** (o crea una planilla nueva en blanco).

> La app reconoce las columnas por su título: *Patente*, *Nombre*, *Teléfono*, *Depto/Unidad*, *Marca*, *Modelo*, *Color*, *Correo* y *Observaciones*. Si falta alguna, la crea sola al final.
> El Form puede seguir funcionando: lo que llegue por el Form aparece en la app, y viceversa.
>
> Si los datos **no** están en la primera pestaña, escribe el nombre de la pestaña en `NOMBRE_HOJA` al comienzo de `Code.gs` (por ejemplo `'Respuestas de formulario 1'`).

## 2. Pegar el código

> ⚠️ **Si ya tienes otro script en esa planilla** (por ejemplo, el visor que genera el PDF), **no lo reemplaces**. Usa la opción B para crear un proyecto aparte.

**Opción A: la planilla no tiene script.** En la planilla: **Extensiones → Apps Script**.

**Opción B: proyecto aparte (conserva el visor antiguo).**
1. Entra a **script.google.com → Nuevo proyecto**. Ponle un nombre, por ejemplo "App Patentes".
2. Copia el **ID de la planilla**, que es lo que va entre `/d/` y `/edit` en su URL.
3. Más abajo, al pegar `Code.gs`, escribe ese ID en `ID_PLANILLA`: `const ID_PLANILLA = 'el-id-aquí';`

Luego, en ambos casos:

1. Abre el editor de Apps Script.
2. En el archivo `Código.gs` que aparece, **borra todo** y pega el contenido de **`Code.gs`**.
3. Haz clic en **＋ (Agregar archivo) → HTML** y nómbralo exactamente **`Index`** (sin “.html”).
4. Borra lo que trae y pega el contenido de **`Index.html`**.
5. Guarda (ícono de disquete o Ctrl+S).

## 3. Definir el PIN de acceso

1. En Apps Script, menú izquierdo: **⚙️ Configuración del proyecto**.
2. Abajo, en **Propiedades de secuencia de comandos**, haz clic en **Agregar propiedad**:
   - Propiedad: `PIN`
   - Valor: el PIN que usarán los guardias (por ejemplo, 6 dígitos que no sean obvios).
3. **Guardar propiedades de la secuencia de comandos**.

Para cambiar el PIN después, edita ese valor. Todos los celulares deberán ingresar el nuevo.

## 4. Publicar la app

1. Arriba a la derecha: **Implementar → Nueva implementación**.
2. En el engranaje junto a “Seleccionar tipo”, elige **Aplicación web**.
3. Configura:
   - **Ejecutar como:** Yo
   - **Quién tiene acceso:** Cualquier usuario
4. **Implementar** → **Autorizar acceso** → elige tu cuenta.
   - Si aparece “Google no ha verificado esta app”: **Configuración avanzada → Ir a (nombre del proyecto)**. Es normal: la app es tuya.
5. Copia la **URL de la aplicación web** (termina en `/exec`).

## 5. Usarla en los celulares

- Abre la URL en el celular e ingresa el PIN (queda recordado en ese teléfono; el botón **Salir** lo borra).
- Para tenerla como app: en Chrome, **⋮ → Agregar a la pantalla principal**; en iPhone (Safari), **Compartir → Agregar a inicio**.

---

## Si modificas el código más adelante

**Implementar → Administrar implementaciones → ✏️ (editar) → Versión: Nueva versión → Implementar.**
Así la URL se mantiene igual.

## Seguridad

- Cualquiera con la URL ve la pantalla del PIN, pero **sin el PIN no puede ver ni cambiar datos**: el PIN se verifica en el servidor de Google.
- Comparte el PIN solo con el personal. Si alguien deja de trabajar ahí, cambia el PIN (paso 3).
- La planilla sigue siendo privada en tu Drive. Google guarda el historial de versiones (**Archivo → Historial de versiones**) por si se borra algo por error.

## Límites gratuitos

Apps Script gratis permite miles de consultas al día, mucho más de lo que necesita un estacionamiento. La primera carga tarda 1 a 3 segundos; después la búsqueda es instantánea, porque la lista queda en el teléfono y se actualiza sola cada minuto o con **↻ Actualizar**.
