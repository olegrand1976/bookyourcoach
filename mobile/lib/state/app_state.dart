import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user.dart';

class AppState extends ChangeNotifier {
  String? _token;
  UserProfile? _me;
  Locale _locale = const Locale('fr');

  static const _localeKey = 'app_locale';

  String? get token => _token;
  UserProfile? get me => _me;
  Locale get locale => _locale;

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

  void setAuthenticated(bool authenticated) {
    _token = authenticated ? (_token ?? 'set') : null;
    notifyListeners();
  }

  AppState() {
    _init();
  }

  Future<void> _init() async {
    final prefs = await SharedPreferences.getInstance();
    final code = prefs.getString(_localeKey);
    if (code != null && code.isNotEmpty) {
      _locale = Locale(code);
      notifyListeners();
    }
  }

  Future<void> setLocale(Locale newLocale) async {
    _locale = newLocale;
    notifyListeners();
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_localeKey, newLocale.languageCode);
  }
}