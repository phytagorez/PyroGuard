/**
 * PyroGuard — ekspor analisis historis dan geometri asli wilayah.
 * Jalankan di GEE Code Editor yang punya akses ke asset Wilayah.
 * Tanggal dan lokasi berikut berasal dari parameter pengguna, belum diverifikasi independen.
 * CSV berisi rata-rata seluruh wilayah, bukan nilai keenam zona secara terpisah.
 */
var wilayahFC = ee.FeatureCollection('projects/pyroguard-513617/assets/Wilayah');
var areaAnalisis = wilayahFC.geometry();
var lokasi = ee.Geometry.Point([112.9530, -7.9425]);
var namaLokasi = 'Bantengan';
var tanggalKebakaran = ee.Date('2023-08-29');
var sebelumMulai = tanggalKebakaran.advance(-30, 'day');
var sebelumSelesai = tanggalKebakaran;
var sesudahMulai = tanggalKebakaran.advance(1, 'day');
var sesudahSelesai = tanggalKebakaran.advance(31, 'day');

Map.centerObject(wilayahFC, 11);
Map.addLayer(wilayahFC.style({color: '1b6c63', fillColor: '00000000', width: 2}), {}, 'Wilayah analisis (poligon)');
Map.addLayer(lokasi, {color: 'a93632'}, 'Referensi kebakaran (point)');

var bands = ['B4', 'B8', 'B11', 'B12'];
function maskCloud(image) {
  var scl = image.select('SCL');
  var mask = scl.neq(0).and(scl.neq(1)).and(scl.neq(3)).and(scl.neq(7))
    .and(scl.neq(8)).and(scl.neq(9)).and(scl.neq(10)).and(scl.neq(11));
  return image.select(bands).updateMask(mask).divide(10000)
    .copyProperties(image, ['system:time_start']);
}
var sentinel = ee.ImageCollection('COPERNICUS/S2_SR_HARMONIZED')
  .filterBounds(areaAnalisis).filterDate(sebelumMulai, sesudahSelesai)
  .filter(ee.Filter.lt('CLOUDY_PIXEL_PERCENTAGE', 40)).map(maskCloud);
var s2Sebelum = sentinel.filterDate(sebelumMulai, sebelumSelesai);
var s2Sesudah = sentinel.filterDate(sesudahMulai, sesudahSelesai);
// A fully masked fallback preserves band names when a collection has no observations.
var fallback = ee.Image.constant([0, 0, 0, 0]).rename(bands).selfMask();
function composite(collection) {
  return collection.merge(ee.ImageCollection.fromImages([fallback]))
    .median().clipToCollection(wilayahFC);
}
var citraSebelum = composite(s2Sebelum);
var citraSesudah = composite(s2Sesudah);
var ndviSebelum = citraSebelum.normalizedDifference(['B8', 'B4']).rename('NDVI_sebelum');
var ndviSesudah = citraSesudah.normalizedDifference(['B8', 'B4']).rename('NDVI_sesudah');
var ndmiSebelum = citraSebelum.normalizedDifference(['B8', 'B11']).rename('NDMI_sebelum');
var ndmiSesudah = citraSesudah.normalizedDifference(['B8', 'B11']).rename('NDMI_sesudah');
var nbrSebelum = citraSebelum.normalizedDifference(['B8', 'B12']).rename('NBR_sebelum');
var nbrSesudah = citraSesudah.normalizedDifference(['B8', 'B12']).rename('NBR_sesudah');
var dNBR = nbrSebelum.subtract(nbrSesudah).rename('dNBR');
var visIndex = {min: -0.2, max: 0.8, palette: ['c9d8d1', '789d87', '1b6c63']};
Map.addLayer(ndviSebelum, visIndex, 'NDVI sebelum', false);
Map.addLayer(ndviSesudah, visIndex, 'NDVI sesudah', false);
Map.addLayer(ndmiSebelum, visIndex, 'NDMI sebelum', false);
Map.addLayer(ndmiSesudah, visIndex, 'NDMI sesudah', false);
Map.addLayer(nbrSebelum, visIndex, 'NBR sebelum', false);
Map.addLayer(nbrSesudah, visIndex, 'NBR sesudah', false);
Map.addLayer(dNBR, {min: -0.3, max: 0.8, palette: ['397b70', 'f4f4ee', 'ac3935']}, 'Perubahan NBR (belum diklasifikasi)', false);

var chirps = ee.ImageCollection('UCSB-CHG/CHIRPS/DAILY').filterBounds(areaAnalisis);
var chirpsHarian = chirps.filterDate(tanggalKebakaran, tanggalKebakaran.advance(1, 'day'));
var chirps7Hari = chirps.filterDate(tanggalKebakaran.advance(-7, 'day'), tanggalKebakaran);
function sumRain(collection, name) {
  var empty = ee.Image.constant(0).rename('precipitation').selfMask();
  return collection.merge(ee.ImageCollection.fromImages([empty])).sum().rename(name);
}
var hujanHarian = sumRain(chirpsHarian, 'Hujan_Harian');
var hujan7Hari = sumRain(chirps7Hari, 'Hujan_7_Hari');
Map.addLayer(hujan7Hari.clipToCollection(wilayahFC), {min: 0, max: 30, palette: ['eff5f5', '6ea5b2', '245f75']}, 'Hujan 7 hari (mm)', false);

var modis = ee.ImageCollection('MODIS/061/MOD11A1').filterBounds(areaAnalisis)
  .filterDate(tanggalKebakaran.advance(-7, 'day'), tanggalKebakaran.advance(1, 'day'));
function maskModis(image) {
  var qc = image.select('QC_Day');
  // Mandatory QA, data quality, emissivity error <= 0.01 and LST error <= 1 K.
  var good = qc.bitwiseAnd(3).eq(0).and(qc.rightShift(2).bitwiseAnd(3).eq(0))
    .and(qc.rightShift(4).bitwiseAnd(3).eq(0)).and(qc.rightShift(6).bitwiseAnd(3).eq(0));
  return image.select('LST_Day_1km').updateMask(good).multiply(0.02)
    .subtract(273.15).rename('Suhu_Permukaan');
}
var suhu = modis.map(maskModis)
  .merge(ee.ImageCollection.fromImages([ee.Image.constant(0).rename('Suhu_Permukaan').selfMask()]))
  .mean().clipToCollection(wilayahFC);
Map.addLayer(suhu, {min: 10, max: 40, palette: ['dbe8e6', 'bd964f', 'aa3632']}, 'Suhu permukaan MODIS (°C)', false);

var viirs = ee.ImageCollection('NASA/VIIRS/002/VNP14A1').filterBounds(areaAnalisis)
  .filterDate(tanggalKebakaran.advance(-2, 'day'), tanggalKebakaran.advance(3, 'day'));
var hotspot = viirs.map(function(image) {
  var fireMask = image.select('FireMask');
  var valid = fireMask.eq(3).or(fireMask.eq(5)).or(fireMask.gte(7).and(fireMask.lte(9)));
  // Water (3), land (5), and fire (7–9) are classified observations; cloud (4) and unclassified (6) stay masked.
  return fireMask.gte(7).and(fireMask.lte(9)).updateMask(valid)
    .rename('Jumlah_Hotspot').copyProperties(image, ['system:time_start']);
});
var jumlahHotspot = hotspot
  .merge(ee.ImageCollection.fromImages([ee.Image.constant(0).rename('Jumlah_Hotspot').selfMask()]))
  .sum().clipToCollection(wilayahFC);
Map.addLayer(jumlahHotspot.selfMask(), {min: 1, max: 5, palette: ['d69083', 'a93632']}, 'Deteksi piksel VIIRS (historis)');

// Environmental bands have different native resolutions. A 100m reducer does not add spatial detail.
var gabungan = ee.Image.cat([ndviSebelum, ndviSesudah, ndmiSebelum, ndmiSesudah,
  nbrSebelum, nbrSesudah, hujanHarian, hujan7Hari, suhu, dNBR]);
var rataArea = gabungan.reduceRegion({reducer: ee.Reducer.mean(), geometry: areaAnalisis,
  scale: 100, crs: 'EPSG:4326', maxPixels: 1e10, tileScale: 4});
var proyeksiViirs = ee.Image(ee.ImageCollection('NASA/VIIRS/002/VNP14A1').first()).select('FireMask').projection();
var hotspotArea = jumlahHotspot.reduceRegion({reducer: ee.Reducer.sum(), geometry: areaAnalisis,
  crs: proyeksiViirs, crsTransform: proyeksiViirs.transform(), maxPixels: 1e10, tileScale: 4});
var tabel = ee.FeatureCollection([ee.Feature(null, rataArea)
  .set('Jumlah_Hotspot_Piksel', hotspotArea.get('Jumlah_Hotspot'))
  .set('Tanggal_Kebakaran', tanggalKebakaran.format('YYYY-MM-dd'))
  .set('Lokasi', namaLokasi).set('Longitude', lokasi.coordinates().get(0))
  .set('Latitude', lokasi.coordinates().get(1)).set('Periode_Sebelum', '30 hari')
  .set('Periode_Sesudah', '30 hari').set('Sumber_Asset', 'projects/pyroguard-513617/assets/Wilayah')
  .set('Jumlah_S2_Sebelum', s2Sebelum.size()).set('Jumlah_S2_Sesudah', s2Sesudah.size())
  .set('Jumlah_CHIRPS_Harian', chirpsHarian.size()).set('Jumlah_CHIRPS_7_Hari', chirps7Hari.size())
  .set('Jumlah_MODIS', modis.size()).set('Jumlah_VIIRS', viirs.size())
  .set('Keterangan', 'Analisis historis; deteksi piksel bukan jumlah kejadian kebakaran')]);

Export.table.toDrive({collection: tabel, description: 'Dataset_PyroGuard_Bromo_2023',
  folder: 'PyroGuard', fileNamePrefix: 'Dataset_PyroGuard_Bromo_2023', fileFormat: 'CSV'});
Export.table.toDrive({collection: wilayahFC, description: 'Wilayah_PyroGuard',
  folder: 'PyroGuard', fileNamePrefix: 'Wilayah_PyroGuard', fileFormat: 'GeoJSON'});
// Optional raster export for analysis in QGIS; not automatically imported into the web map.
Export.image.toDrive({image: gabungan.toFloat(), description: 'Raster_PyroGuard_Bromo_2023',
  folder: 'PyroGuard', fileNamePrefix: 'Raster_PyroGuard_Bromo_2023', region: areaAnalisis,
  scale: 100, crs: 'EPSG:4326', maxPixels: 1e10, fileFormat: 'GeoTIFF',
  formatOptions: {cloudOptimized: true}});
print('Asset wilayah (bukan enam zona hasil inferensi):', wilayahFC);
print('Luas wilayah (ha):', areaAnalisis.area(1).divide(10000));
print('Jumlah citra S2 sebelum / sesudah:', s2Sebelum.size(), s2Sesudah.size());
print('Jumlah citra CHIRPS harian / 7 hari:', chirpsHarian.size(), chirps7Hari.size());
print('Jumlah citra MODIS / VIIRS:', modis.size(), viirs.size());
print('Dataset historis:', tabel);
print('Jalankan Tasks untuk CSV dan GeoJSON; unggah kedua hasilnya di menu Dataset & integrasi PyroGuard. Nilai kosong adalah data hilang, bukan nol.');
