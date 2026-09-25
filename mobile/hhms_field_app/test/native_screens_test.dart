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

  testWidgets('inspection task detail hides maintenance finance actions', (
    tester,
  ) async {
    final task = {
      'id': 'task-inspection',
      'number': 'TSK-INSP-01',
      'title': 'Check-out inspection',
      'type': 'checkout_inspection',
      'status': 'assigned',
      'status_label': 'Assigned',
      'priority': 'urgent',
      'building': 'Marina Residence',
      'property': '502',
      'due_date': '2026-09-20',
      'created_by': 'Operations',
      'description': 'Complete the checkout condition report.',
      'activities': <Map<String, dynamic>>[],
    };
    await tester.pumpWidget(
      MaterialApp(
        home: NativeFieldApp(
          user: const {'id': 'u-1', 'name': 'Ahmed', 'role': 'maintainer'},
          request: (method, path, [data]) async =>
              path.endsWith('/task-inspection')
              ? {'task': task}
              : {
                  'tasks': [task],
                },
          upload: (path, fields, file, [extras]) async => {},
          signOut: () async {},
        ),
      ),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.text('All tasks'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('TSK-INSP-01').first);
    await tester.pumpAndSettle();

    await tester.scrollUntilVisible(
      find.text('Accept & start inspection'),
      220,
      scrollable: find.byType(Scrollable).last,
    );
    expect(find.text('Accept & start inspection'), findsOneWidget);
    expect(find.text('Record task cost'), findsNothing);
    expect(find.text('Request office payment'), findsNothing);
    expect(
      find.text('Complete the checkout condition report.'),
      findsOneWidget,
    );
  });

  testWidgets('empty list draft does not crash an inspection', (tester) async {
    final task = {
      'id': 'task-safe-draft',
      'number': 'TSK-SAFE-01',
      'title': 'Routine inspection',
      'type': 'inspection',
      'status': 'accepted',
      'status_label': 'Accepted',
      'building': 'Mirecal Tower',
      'property': '2505',
      'activities': <Map<String, dynamic>>[],
    };
    await tester.pumpWidget(
      MaterialApp(
        home: NativeFieldApp(
          user: const {'id': 'u-1', 'name': 'Ahmed', 'role': 'maintainer'},
          request: (method, path, [data]) async {
            if (path.endsWith('/task-safe-draft/inspection')) {
              return {
                'inspection': {
                  'id': 'inspection-1',
                  'draft': <dynamic>[],
                  'items': [
                    {
                      'id': 'item-1',
                      'area': 'Living room',
                      'name': 'Walls',
                      'photos': <dynamic>[],
                    },
                  ],
                  'inventory': <dynamic>[],
                },
              };
            }
            if (path.endsWith('/task-safe-draft')) return {'task': task};
            return {
              'tasks': [task],
            };
          },
          upload: (path, fields, file, [extras]) async => {},
          signOut: () async {},
        ),
      ),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.text('All tasks'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('TSK-SAFE-01').first);
    await tester.pumpAndSettle();
    await tester.tap(find.text('Continue inspection'));
    await tester.pumpAndSettle();

    expect(find.text('Living room'), findsOneWidget);
    expect(find.textContaining('is not a subtype'), findsNothing);
  });
}
