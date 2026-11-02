import { ImageManipulator, SaveFormat } from 'expo-image-manipulator';

const MAX_SIDE = 1280;
const JPEG_QUALITY = 0.7;

// The server accepts at most 5 MB. A phone photo is often larger, and 1280 px is plenty
// for the report and for the model (which looks at 224×224), so shrink it before upload.
// The result is always JPEG, also when a PNG was picked from the gallery.
export async function compressPhoto({ uri, width, height }: { uri: string; width: number; height: number }): Promise<string> {
  const context = ImageManipulator.manipulate(uri);
  if (Math.max(width, height) > MAX_SIDE) {
    context.resize(width >= height ? { width: MAX_SIDE } : { height: MAX_SIDE });
  }

  const image = await context.renderAsync();
  try {
    const result = await image.saveAsync({ compress: JPEG_QUALITY, format: SaveFormat.JPEG });
    return result.uri;
  } finally {
    // Free the native bitmaps right away instead of waiting for garbage collection.
    image.release();
    context.release();
  }
}
