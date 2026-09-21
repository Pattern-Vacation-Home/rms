import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hhms_field_app/main.dart';

void main() {
  testWidgets('render every field app design screen', (tester) async {
    final fonts = FontLoader('Roboto')
      ..addFont(rootBundle.load('assets/fonts/Roboto-Regular.ttf'))
      ..addFont(rootBundle.load('assets/fonts/Roboto-Medium.ttf'));
    await fonts.load();
    await (FontLoader('MaterialIcons')
          ..addFont(rootBundle.load('assets/fonts/MaterialIcons-Regular.otf')))
        .load();
    tester.view.physicalSize = const Size(390, 844);
    tester.view.devicePixelRatio = 1;
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });

    for (var index = 0; index < screens.length; index++) {
      await tester.pumpWidget(
        FieldApp(key: ValueKey(index), initialScreen: index),
      );
      await tester.pumpAndSettle();
      final slug = screens[index].title.toLowerCase().replaceAll(
        RegExp(r'[^a-z0-9]+'),
        '-',
      );
      await expectLater(
        find.byType(MaterialApp),
        matchesGoldenFile(
          'screens/${(index + 1).toString().padLeft(2, '0')}-$slug.png',
        ),
      );
    }
  });
}
