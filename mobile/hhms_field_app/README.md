# HHMS Field for Android

The app connects to `https://rms.dt-server.com`. After staff sign in, Flutter renders native Operations and Maintainer screens with live data from the Laravel field endpoints in `routes/field.php`. The sign-in page, task lists, task details, room checklist, inspection photos, inventory counts, review, request and approval screens use the approved navy and teal design. The launcher and header use the existing Patern mark.

The app keeps an authenticated WebView in the background to share the existing Laravel session and CSRF token with the new JSON endpoints. The WebView site remains available as a fallback if the server has not yet deployed those endpoints. Photos are selected using the Android camera/gallery and uploaded to the existing inspection endpoint.

Build an internal APK:

```powershell
flutter build apk --release
```

The APK is at `build/app/outputs/flutter-apk/app-release.apk`. This build uses a debug signing key for internal distribution. A permanent application ID and release signing key are required for Google Play.

Real-device checks are still needed for sign-in, photo capture, inspection submission, and approval. The existing 23-screen design goldens remain in `test/screens` and their contact sheets in `screenshots`.
