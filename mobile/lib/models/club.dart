class Club {
  final int id;
  final String name;
  final String address;
  final String city;
  final String postalCode;
  final String country;
  final String? description;
  final List<String>? facilities;
  final bool isActive;
  final DateTime createdAt;
  final DateTime updatedAt;

  Club({
    required this.id,
    required this.name,
    required this.address,
    required this.city,
    required this.postalCode,
    required this.country,
    this.description,
    this.facilities,
    required this.isActive,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Club.fromJson(Map<String, dynamic> json) {
    return Club(
      id: json['id'] ?? 0,
      name: json['name']?.toString() ?? '',
      address: json['address']?.toString() ?? '',
      city: json['city']?.toString() ?? '',
      postalCode: json['postal_code']?.toString() ?? '',
      country: json['country']?.toString() ?? '',
      description: json['description']?.toString(),
      facilities: json['facilities'] != null 
          ? List<String>.from(json['facilities'])
          : null,
      isActive: json['is_active'] ?? json['status'] == 'active' ?? true,
      createdAt: json['created_at'] != null 
          ? DateTime.parse(json['created_at'].toString())
          : DateTime.now(),
      updatedAt: json['updated_at'] != null 
          ? DateTime.parse(json['updated_at'].toString())
          : DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'address': address,
      'city': city,
      'postal_code': postalCode,
      'country': country,
      'description': description,
      'facilities': facilities,
      'is_active': isActive,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  String get fullAddress {
    return '$address, $postalCode $city, $country';
  }

  String get statusDisplay {
    return isActive ? 'Actif' : 'Inactif';
  }

  // Copie avec modifications
  Club copyWith({
    int? id,
    String? name,
    String? address,
    String? city,
    String? postalCode,
    String? country,
    String? description,
    List<String>? facilities,
    bool? isActive,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return Club(
      id: id ?? this.id,
      name: name ?? this.name,
      address: address ?? this.address,
      city: city ?? this.city,
      postalCode: postalCode ?? this.postalCode,
      country: country ?? this.country,
      description: description ?? this.description,
      facilities: facilities ?? this.facilities,
      isActive: isActive ?? this.isActive,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  String toString() {
    return 'Club(id: $id, name: $name, city: $city)';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is Club && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}
