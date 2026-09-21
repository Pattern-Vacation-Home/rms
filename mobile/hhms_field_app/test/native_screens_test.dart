import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hhms_field_app/native_field_app.dart';

void main() {
  Future<void> loadFonts() async {
    await (FontLoader('Roboto')
          ..addFont(rootBundle.load('assets/fonts/Roboto-Regular.ttf'))
          ..addFont(rootBundle.load('assets/fonts/Roboto-Medium.ttf')))
        .load();
    await (FontLoader('MaterialIcons')
          ..addFont(rootBundle.load('assets/fonts/MaterialIcons-Regular.otf')))
        .load();
  }

  testWidgets('native maintainer screens render live task data', (
    tester,
  ) async {
    await loadFonts();
    tester.view.physicalSize = const Size(390, 844);
    tester.view.devicePixelRatio = 1;
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });
    final tasks = [
      {
        'id': 'task-1',
        'number': 'TSK-0919-02',
        'title': 'Check-out inspection',
        'type': 'checkout_inspection',
        'status': 'assigned',
        'status_label': 'Assigned',
        'building': 'Marina Residence',
        'property': '502',
        'due_date': '2026-09-20',
      },
    ];
    await tester.pumpWidget(
      MaterialApp(
        home: NativeFieldApp(
          user: const {
            'id': 'u-1',
            'name': 'Ahmed Hassan',
            'role': 'maintainer',
          },
          request: (method, path, [data]) async => {'tasks': tasks},
          upload: (path, fields, file, [extras]) async => {},
          signOut: () async {},
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('TSK-0919-02'), findsOneWidget);
    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile('screens/native-maintainer-home.png'),
    );
    await tester.tap(find.text('My inspections').first);
    await tester.pumpAndSettle();
    expect(find.text('TSK-0919-02'), findsOneWidget);
    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile('screens/native-maintainer-inspections.png'),
    );
  });

  testWidgets('native operations queue renders inspection data', (
    tester,
  ) async {
    await loadFonts();
    tester.view.physicalSize = const Size(390, 844);
    tester.view.devicePixelRatio = 1;
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });
    await tester.pumpWidget(
      MaterialApp(
        home: NativeFieldApp(
          user: const {'id': 'u-2', 'name': 'Sarah Ahmed', 'role': 'admin'},
          request: (method, path, [data]) async => {
            'inspections': [
              {
                'id': 'inspection-1',
                'number': 'INSP-0919-02',
                'type': 'Check Out',
                'status': 'submitted',
                'building': 'Marina Residence',
                'property': '502',
              },
            ],
          },
          upload: (path, fields, file, [extras]) async => {},
          signOut: () async {},
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('INSP-0919-02'), findsOneWidget);
    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile('screens/native-operations-home.png'),
    );
  });
}
