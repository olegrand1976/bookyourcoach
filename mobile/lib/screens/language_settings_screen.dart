import 'package:flutter/material.dart';

class LanguageSettingsScreen extends StatefulWidget {
  const LanguageSettingsScreen({super.key});

  @override
  State<LanguageSettingsScreen> createState() => _LanguageSettingsScreenState();
}

class _LanguageSettingsScreenState extends State<LanguageSettingsScreen> {
  Locale _locale = const Locale('fr');

  void _setLocale(Locale locale) {
    setState(() { _locale = locale; });
    // Note: Full i18n via flutter_localizations + intl is scaffolded but
    // for brevity this only stores selection in memory. Could persist via
    // SharedPreferences and rebuild MaterialApp with locale.
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Langue')),
      body: ListView(
        children: [
          RadioListTile<Locale>(
            title: const Text('Français'),
            value: const Locale('fr'),
            groupValue: _locale,
            onChanged: (v) => _setLocale(v!),
          ),
          RadioListTile<Locale>(
            title: const Text('English'),
            value: const Locale('en'),
            groupValue: _locale,
            onChanged: (v) => _setLocale(v!),
          ),
        ],
      ),
    );
  }
}

