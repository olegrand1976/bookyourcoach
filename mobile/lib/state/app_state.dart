import 'package:flutter/foundation.dart';
import '../models/user.dart';

class AppState extends ChangeNotifier {
  String? _token;
  UserProfile? _me;

  String? get token => _token;
  UserProfile? get me => _me;

  bool get isAuthenticated => _token != null && _token!.isNotEmpty;
  bool get isTeacher => _me?.isTeacher == true;
  bool get isStudent => _me?.isStudent == true;

  void setToken(String? token) {
    _token = token;
    notifyListeners();
  }

  void setMe(UserProfile? profile) {
    _me = profile;
    notifyListeners();
  }
}