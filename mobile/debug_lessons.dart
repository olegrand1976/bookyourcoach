import 'dart:convert';
import 'dart:io';

void main() async {
  print('=== DIAGNOSTIC API COURS ===');
  
  // Test 1: Authentification
  print('\n1. Test authentification...');
  final token = await testAuthentication();
  
  if (token != null) {
    // Test 2: API des cours
    print('\n2. Test API des cours...');
    await testLessonsApi(token);
    
    // Test 3: API des disponibilités
    print('\n3. Test API des disponibilités...');
    await testAvailabilitiesApi(token);
  }
}

Future<String?> testAuthentication() async {
  try {
    final client = HttpClient();
    final request = await client.postUrl(Uri.parse('http://10.0.2.2:8081/api/auth/login'));
    request.headers.set('Content-Type', 'application/json');
    
    final body = jsonEncode({
      'email': 'sophie.fixed@example.com',
      'password': 'password123',
    });
    request.write(body);
    
    final response = await request.close();
    final data = await response.transform(utf8.decoder).join();
    
    if (response.statusCode == 200) {
      final json = jsonDecode(data);
      final token = json['token'];
      final user = json['user'];
      print('✅ Authentification réussie');
      print('   Token: ${token.substring(0, 20)}...');
      print('   User: ${user['name']} (${user['role']})');
      print('   can_act_as_teacher: ${user['can_act_as_teacher']}');
      client.close();
      return token;
    } else {
      print('❌ Échec authentification: ${response.statusCode}');
      print('   Réponse: $data');
      client.close();
      return null;
    }
  } catch (e) {
    print('❌ Erreur authentification: $e');
    return null;
  }
}

Future<void> testLessonsApi(String token) async {
  try {
    final client = HttpClient();
    final request = await client.getUrl(Uri.parse('http://10.0.2.2:8081/api/teacher/lessons'));
    request.headers.set('Authorization', 'Bearer $token');
    request.headers.set('Accept', 'application/json');
    
    final response = await request.close();
    final data = await response.transform(utf8.decoder).join();
    
    print('📊 API Cours - Status: ${response.statusCode}');
    print('📊 Réponse: $data');
    
    if (response.statusCode == 200) {
      if (data.trim().isEmpty) {
        print('⚠️  Réponse vide - pas de cours trouvés');
      } else {
        try {
          final json = jsonDecode(data);
          print('✅ Cours chargés avec succès');
          print('   Type de réponse: ${json.runtimeType}');
          if (json is List) {
            print('   Nombre de cours: ${json.length}');
          } else if (json is Map) {
            print('   Clés disponibles: ${json.keys.toList()}');
          }
        } catch (e) {
          print('❌ Erreur parsing JSON: $e');
        }
      }
    } else {
      print('❌ Erreur API cours: ${response.statusCode}');
    }
    client.close();
  } catch (e) {
    print('❌ Erreur requête cours: $e');
  }
}

Future<void> testAvailabilitiesApi(String token) async {
  try {
    final client = HttpClient();
    final request = await client.getUrl(Uri.parse('http://10.0.2.2:8081/api/teacher/availabilities'));
    request.headers.set('Authorization', 'Bearer $token');
    request.headers.set('Accept', 'application/json');
    
    final response = await request.close();
    final data = await response.transform(utf8.decoder).join();
    
    print('📊 API Disponibilités - Status: ${response.statusCode}');
    print('📊 Réponse: $data');
    
    if (response.statusCode == 200) {
      if (data.trim().isEmpty) {
        print('⚠️  Réponse vide - pas de disponibilités trouvées');
      } else {
        try {
          final json = jsonDecode(data);
          print('✅ Disponibilités chargées avec succès');
          print('   Type de réponse: ${json.runtimeType}');
          if (json is List) {
            print('   Nombre de disponibilités: ${json.length}');
          } else if (json is Map) {
            print('   Clés disponibles: ${json.keys.toList()}');
          }
        } catch (e) {
          print('❌ Erreur parsing JSON: $e');
        }
      }
    } else {
      print('❌ Erreur API disponibilités: ${response.statusCode}');
    }
    client.close();
  } catch (e) {
    print('❌ Erreur requête disponibilités: $e');
  }
}
