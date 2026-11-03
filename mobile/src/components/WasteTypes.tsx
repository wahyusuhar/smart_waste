import Ionicons from '@expo/vector-icons/Ionicons';
import { StyleSheet, Text, View } from 'react-native';

import { colors, fonts, radius, type } from '../theme';

// Static content. The examples follow what the training data actually contains (ml/DATASET.md):
// cardboard, for instance, is a separate class in the dataset and was not trained, so it is not listed.
const TYPES = [
  { icon: 'leaf-outline', label: 'Organik', examples: 'Sisa makanan, kulit buah, sayuran' },
  { icon: 'bag-outline', label: 'Plastik', examples: 'Botol, kantong, kemasan' },
  { icon: 'newspaper-outline', label: 'Kertas', examples: 'Koran, majalah, kertas bekas' },
] as const;

export function WasteTypes() {
  return (
    <View>
      <Text style={type.heading}>Jenis sampah yang dikenali</Text>
      <View style={styles.grid}>
        {TYPES.map((item) => (
          <View key={item.label} style={styles.card}>
            <Ionicons name={item.icon} size={24} color={colors.gray700} />
            <Text style={styles.label}>{item.label}</Text>
            <Text style={type.caption}>{item.examples}</Text>
          </View>
        ))}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  grid: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 12,
  },
  card: {
    flex: 1,
    padding: 12,
    gap: 6,
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.gray200,
    borderRadius: radius.control,
  },
  label: {
    fontFamily: fonts.semibold,
    fontSize: 14,
    lineHeight: 20,
    color: colors.gray900,
  },
});
