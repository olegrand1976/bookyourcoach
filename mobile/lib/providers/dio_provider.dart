import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

// Configuration Dio
final dioProvider = Provider<Dio>((ref) {
  final dio = Dio();
  
  // Configuration de base
  dio.options.baseUrl = 'http://10.0.2.2:8081/api';
  dio.options.connectTimeout = const Duration(seconds: 30);
  dio.options.receiveTimeout = const Duration(seconds: 30);
  
  // Intercepteurs
  dio.interceptors.add(InterceptorsWrapper(
    onRequest: (options, handler) {
      // Ajouter des headers par défaut
      options.headers['Content-Type'] = 'application/json';
      options.headers['Accept'] = 'application/json';
      handler.next(options);
    },
    onResponse: (response, handler) {
      handler.next(response);
    },
    onError: (error, handler) {
      handler.next(error);
    },
  ));
  
  return dio;
});