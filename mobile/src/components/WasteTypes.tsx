import { Image } from 'expo-image';
import { StyleSheet, Text, View } from 'react-native';

import { colors, fonts, type } from '../theme';

// Static content: photos from the demo data (web/database/seeders/photos), shrunk to 300 px.
const TYPES = [
  { label: 'Organik', photo: require('../../assets/categories/organic.jpg') },
  { label: 'Plastik', photo: require('../../assets/categories/plastic.jpg') },
  { label: 'Kertas', photo: require('../../assets/categories/paper.jpg') },
];

export function WasteTypes() {
  return (
    <View>
      <Text style={type.heading}>Jenis sampah</Text>
      <View style={styles.row}>
        {TYPES.map((item) => (
          <View key={item.label} style={styles.item}>
            <Image source={item.photo} style={styles.photo} contentFit="cover" accessibilityLabel={`Contoh sampah ${item.label}`} />
            <Text style={styles.label}>{item.label}</Text>
          </View>
        ))}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 12,
  },
  item: {
    flex: 1,
    alignItems: 'center',
    gap: 8,
  },
  photo: {
    width: '100%',
    aspectRatio: 1,
    borderRadius: 16,
    backgroundColor: colors.gray100,
  },
  label: {
    fontFamily: fonts.semibold,
    fontSize: 14,
    lineHeight: 20,
    color: colors.gray900,
  },
});
