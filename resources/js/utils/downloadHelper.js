import api from '../services/api';

/**
 * Ekstrak nama file dari header HTTP Content-Disposition.
 * Mendukung format standar (filename="...") dan RFC 5987 / 6266 (filename*=UTF-8''...).
 *
 * @param {Object} headers - Response headers dari Axios
 * @param {string} defaultFilename - Nama file fallback jika header tidak ditemukan
 * @returns {string}
 */
export function extractFilenameFromHeader(headers = {}, defaultFilename = 'download') {
  const disposition = headers?.['content-disposition'] || headers?.['Content-Disposition'];
  if (!disposition || typeof disposition !== 'string') {
    return defaultFilename;
  }

  // 1. Cek parameter filename*=UTF-8'' (RFC 5987 / 6266)
  const utf8Matches = /filename\*=UTF-8''([^;\n]*)/i.exec(disposition);
  if (utf8Matches && utf8Matches[1]) {
    try {
      return decodeURIComponent(utf8Matches[1].trim());
    } catch {
      return utf8Matches[1].trim();
    }
  }

  // 2. Cek parameter standar filename="..."
  const standardMatches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/i.exec(disposition);
  if (standardMatches && standardMatches[1]) {
    return standardMatches[1].replace(/['"]/g, '').trim();
  }

  return defaultFilename;
}

/**
 * Download file dari Axios Blob response secara aman di memori browser.
 * Mencegah pemblokiran Google Chrome "Insecure download blocked" karena:
 * - Menggunakan URL Blob lokal di memori browser (blob:http://...) bukan navigasi HTTP langsung.
 * - Menggunakan hidden anchor element dengan pembersihan otomatis.
 * - Melakukan validasi ketat MIME type (mencegah payload error JSON/HTML terunduh sebagai berkas rusak).
 *
 * @param {import('axios').AxiosResponse} response - Axios response object dengan responseType: 'blob'
 * @param {string} defaultFilename - Nama file cadangan jika tidak ada di header
 * @returns {Promise<{ success: boolean, filename: string }>}
 */
export async function downloadBlobFile(response, defaultFilename = 'download') {
  if (!response || !response.data) {
    throw new Error('Respons server kosong atau tidak valid.');
  }

  const rawContentType = (response.headers?.['content-type'] || response.data?.type || '').toLowerCase();

  // 1. Validasi: Bila server mengembalikan error dalam bentuk JSON atau HTML (bukan berkas binary)
  if (rawContentType.includes('application/json') || rawContentType.includes('text/html')) {
    let serverMessage = 'Terjadi kesalahan saat memproses berkas laporan di server.';
    try {
      if (typeof response.data.text === 'function') {
        const text = await response.data.text();
        if (rawContentType.includes('application/json')) {
          const parsed = JSON.parse(text);
          serverMessage = parsed.message || parsed.error || serverMessage;
        } else {
          // Cuplikan teks singkat jika HTML error
          const cleanText = text.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
          if (cleanText) {
            serverMessage = cleanText.length > 120 ? cleanText.substring(0, 120) + '...' : cleanText;
          }
        }
      }
    } catch {
      // Abaikan parsing error, gunakan pesan fallback
    }
    throw new Error(serverMessage);
  }

  // 2. Ekstrak nama berkas yang sah
  const filename = extractFilenameFromHeader(response.headers, defaultFilename);

  // 3. Tentukan MIME Type yang tepat untuk Blob browser
  let resolvedMime = rawContentType;
  const lowerName = filename.toLowerCase();
  if (lowerName.endsWith('.pdf')) {
    resolvedMime = 'application/pdf';
  } else if (lowerName.endsWith('.xlsx')) {
    resolvedMime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
  } else if (!resolvedMime) {
    resolvedMime = 'application/octet-stream';
  }

  // Bungkus response data menjadi instance Blob dengan MIME type eksplisit
  const blob = response.data instanceof Blob
    ? (response.data.type === resolvedMime ? response.data : new Blob([response.data], { type: resolvedMime }))
    : new Blob([response.data], { type: resolvedMime });

  // 4. Inisiasi download aman via Object URL di memori (blob: scheme)
  const blobUrl = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.style.display = 'none';
  link.href = blobUrl;
  link.download = filename;

  document.body.appendChild(link);
  link.click();
  link.remove();

  // 5. Bersihkan Object URL setelah browser selesai memproses stream (10 detik)
  setTimeout(() => {
    window.URL.revokeObjectURL(blobUrl);
  }, 10000);

  return { success: true, filename };
}

/**
 * Alias backward compatibility untuk fungsi downloadBlob.
 */
export const downloadBlob = downloadBlobFile;

/**
 * Helper terpusat untuk memanggil endpoint laporan secara terautentikasi dan mengunduh berkasnya.
 *
 * @param {string} relativeEndpoint - Relative API path (misal: '/reports/income-statement/export/pdf')
 * @param {Object} params - Query parameters (filter tanggal, dsb)
 * @param {string} fallbackFilename - Nama default jika Content-Disposition tidak tersedia
 * @returns {Promise<{ success: boolean, filename: string }>}
 */
export async function downloadFromApi(relativeEndpoint, params = {}, fallbackFilename = 'laporan.pdf') {
  // Pastikan path selalu relatif untuk menghindari peringatan insecure mixed content
  const cleanEndpoint = relativeEndpoint.startsWith('/') ? relativeEndpoint : `/${relativeEndpoint}`;

  const response = await api.get(cleanEndpoint, {
    params,
    responseType: 'blob',
    timeout: 90000, // 90 detik untuk dataset besar
  });

  return await downloadBlobFile(response, fallbackFilename);
}

export default {
  extractFilenameFromHeader,
  downloadBlobFile,
  downloadBlob,
  downloadFromApi,
};

