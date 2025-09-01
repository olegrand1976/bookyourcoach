import 'dart:io';

String platformBaseApiUrl() {
  if (Platform.isAndroid) {
    return 'http://10.0.2.2:8081/api';
  }
  return 'http://localhost:8081/api';
}


