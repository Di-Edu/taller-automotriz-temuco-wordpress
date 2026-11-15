/**
 * Registro de Patentes — servidor (Google Apps Script)
 * Los datos viven en la planilla a la que está vinculado este script.
 * El PIN se configura en: Configuración del proyecto → Propiedades de secuencia de comandos → PIN
 */

// ID de la planilla (lo que va entre /d/ y /edit en su URL).
// Vacío = la planilla a la que está vinculado este script (Extensiones → Apps Script).
const ID_PLANILLA = '';

// Nombre de la pestaña donde están los datos. Vacío = la primera pestaña.
const NOMBRE_HOJA = '';

// Cómo se reconoce cada dato por el título de la columna.
// Si falta una columna, se crea al final con el "titulo" indicado.
const CAMPOS = {
  fecha:    { re: /marca temporal|timestamp|fecha/i,              titulo: 'Fecha' },
  patente:  { re: /patente|placa|ppu/i,                           titulo: 'Patente' },
  nombre:   { re: /nombre|propietario|due[ñn]o|conductor/i,       titulo: 'Nombre' },
  telefono: { re: /tel[eé]fono|fono|celular|whats|m[oó]vil/i,     titulo: 'Teléfono' },
  unidad:   { re: /depto|departamento|unidad|casa|oficina|local/i, titulo: 'UNIDAD/OFICINA' },
  anexo:    { re: /anexo/i,                                       titulo: 'ANEXO INSTITUCIONAL' },
  marca:    { re: /^(?!.*temporal).*(marca|veh[ií]culo)/i,        titulo: 'MARCA' },
  modelo:   { re: /modelo/i,                                      titulo: 'MODELO' },
  color:    { re: /color/i,                                       titulo: 'COLOR' },
  obs:      { re: /observ|coment|nota/i,                          titulo: 'OBSERVACIONES' }
};
const EDITABLES = ['patente', 'nombre', 'telefono', 'unidad', 'anexo', 'marca', 'modelo', 'color', 'obs'];
// Columnas que siempre deben existir; las demás se crean solo si se guarda un dato en ellas.
const OBLIGATORIAS = ['fecha', 'patente', 'nombre', 'telefono'];

function doGet() {
  return HtmlService.createHtmlOutputFromFile('Index')
    .setTitle('Registro de Patentes')
    .addMetaTag('viewport', 'width=device-width, initial-scale=1');
}

// ---------- utilidades ----------
function norm_(s) { return String(s == null ? '' : s).toUpperCase().replace(/[^A-Z0-9]/g, ''); }

function hoja_() {
  const ss = ID_PLANILLA ? SpreadsheetApp.openById(ID_PLANILLA) : SpreadsheetApp.getActiveSpreadsheet();
  if (!ss) throw new Error('Configura ID_PLANILLA en Code.gs');
  const sh = NOMBRE_HOJA ? ss.getSheetByName(NOMBRE_HOJA) : ss.getSheets()[0];
  if (!sh) throw new Error('No existe la pestaña "' + NOMBRE_HOJA + '"');
  return sh;
}

function verificarPin_(pin) {
  const real = PropertiesService.getScriptProperties().getProperty('PIN');
  if (!real) throw new Error('Falta configurar el PIN en las propiedades del proyecto.');
  if (String(pin || '') !== String(real)) {
    Utilities.sleep(1500); // frena intentos repetidos
    throw new Error('PIN incorrecto');
  }
}

/** Devuelve {campo: índice de columna (0-based)} y crea las columnas de "crear" que falten. */
function columnas_(sh, crear) {
  crear = crear || OBLIGATORIAS;
  const nCols = sh.getLastColumn();
  const headers = nCols ? sh.getRange(1, 1, 1, nCols).getDisplayValues()[0] : [];
  const map = {};
  headers.forEach(function (h, i) {
    if (!String(h).trim()) return;
    for (const k in CAMPOS) {
      if (!(k in map) && CAMPOS[k].re.test(h)) { map[k] = i; break; }
    }
  });
  let next = headers.length;
  for (const k in CAMPOS) {
    if (!(k in map) && crear.indexOf(k) >= 0) {
      sh.getRange(1, next + 1).setValue(CAMPOS[k].titulo);
      map[k] = next++;
    }
  }
  return map;
}

function filaAObjeto_(row, map, numFila) {
  const o = { fila: numFila };
  for (const k in map) o[k] = row[map[k]] == null ? '' : String(row[map[k]]);
  return o;
}

// ---------- API usada por la app ----------
function listar(pin) {
  verificarPin_(pin);
  const sh = hoja_();
  const map = columnas_(sh);
  const last = sh.getLastRow();
  if (last < 2) return [];
  const vals = sh.getRange(2, 1, last - 1, sh.getLastColumn()).getDisplayValues();
  const out = [];
  vals.forEach(function (r, i) {
    const o = filaAObjeto_(r, map, i + 2);
    if (norm_(o.patente)) out.push(o);
  });
  return out;
}

/** Busca la fila real de un registro (por si alguien movió/borró filas en la planilla). */
function ubicarFila_(sh, map, fila, patenteOriginal) {
  const p = norm_(patenteOriginal);
  if (fila >= 2 && fila <= sh.getLastRow()) {
    const v = sh.getRange(fila, map.patente + 1).getDisplayValue();
    if (norm_(v) === p) return fila;
  }
  const last = sh.getLastRow();
  if (last < 2) return 0;
  const col = sh.getRange(2, map.patente + 1, last - 1, 1).getDisplayValues();
  for (let i = 0; i < col.length; i++) if (norm_(col[i][0]) === p) return i + 2;
  return 0;
}

/**
 * Crea o edita un registro.
 * rec: {patente, nombre, ...}; fila y patenteOriginal solo al editar.
 * forzar: true para inscribir aunque la patente ya exista.
 */
function guardar(pin, rec, fila, patenteOriginal, forzar) {
  verificarPin_(pin);
  const lock = LockService.getScriptLock();
  lock.waitLock(20000);
  try {
    const sh = hoja_();
    const conDato = EDITABLES.filter(function (k) { return String(rec[k] == null ? '' : rec[k]).trim(); });
    const map = columnas_(sh, OBLIGATORIAS.concat(conDato));
    const nCols = sh.getLastColumn();
    const patente = norm_(rec.patente);
    if (!patente) throw new Error('Falta la patente');
    if (!String(rec.nombre || '').trim()) throw new Error('Falta el nombre');

    let numFila = 0;
    if (fila) {
      numFila = ubicarFila_(sh, map, fila, patenteOriginal);
      if (!numFila) throw new Error('No encontré el registro original; actualiza la lista.');
    } else if (!forzar && ubicarFila_(sh, map, 0, patente)) {
      return { duplicada: true };
    }

    let row;
    if (numFila) {
      row = sh.getRange(numFila, 1, 1, nCols).getValues()[0];
    } else {
      row = new Array(nCols).fill('');
      row[map.fecha] = new Date();
    }
    EDITABLES.forEach(function (k) {
      if (k in rec && k in map) row[map[k]] =String(rec[k] == null ? '' : rec[k]).trim();
    });
    row[map.patente] = patente;

    if (!numFila) numFila = sh.getLastRow() + 1;
    // Teléfono como texto para que no se pierda el "+" ni los ceros
    sh.getRange(numFila, map.telefono + 1).setNumberFormat('@');
    sh.getRange(numFila, 1, 1, nCols).setValues([row]);
    SpreadsheetApp.flush();
    return { ok: true, registro: filaAObjeto_(sh.getRange(numFila, 1, 1, nCols).getDisplayValues()[0], map, numFila) };
  } finally {
    lock.releaseLock();
  }
}

function eliminar(pin, fila, patente) {
  verificarPin_(pin);
  const lock = LockService.getScriptLock();
  lock.waitLock(20000);
  try {
    const sh = hoja_();
    const map = columnas_(sh);
    const numFila = ubicarFila_(sh, map, fila, patente);
    if (!numFila) throw new Error('No encontré el registro; actualiza la lista.');
    sh.deleteRow(numFila);
    return { ok: true };
  } finally {
    lock.releaseLock();
  }
}
