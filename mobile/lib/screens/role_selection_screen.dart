import 'package:flutter/material.dart';

class RoleSelectionScreen extends StatefulWidget {
  const RoleSelectionScreen({super.key});

  @override
  State<RoleSelectionScreen> createState() => _RoleSelectionScreenState();
}

class _RoleSelectionScreenState extends State<RoleSelectionScreen> {
  bool asStudent = false;
  bool asTeacher = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sélection du rôle')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Choisissez votre rôle:'),
            CheckboxListTile(
              title: const Text('Élève'),
              value: asStudent,
              onChanged: (v) => setState(() => asStudent = v ?? false),
            ),
            CheckboxListTile(
              title: const Text('Enseignant'),
              value: asTeacher,
              onChanged: (v) => setState(() => asTeacher = v ?? false),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: (!asStudent && !asTeacher) ? null : () {
                  Navigator.of(context).pop({'student': asStudent, 'teacher': asTeacher});
                },
                child: const Text('Continuer'),
              ),
            )
          ],
        ),
      ),
    );
  }
}