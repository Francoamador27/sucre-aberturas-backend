@php
    $patient = $event->patient;
    $doctor = $event->doctor;
    
    // Formatear fechas de manera más legible
    $startDate = $event->start ? $event->start->format('d/m/Y H:i') : 'No especificado';
    $endDate = $event->end ? $event->end->format('d/m/Y H:i') : 'No especificado';
    
    // Construir nombre completo del paciente de forma segura
    $patientFullName = collect([
        $patient?->nompa,
        $patient?->apepa
    ])->filter()->implode(' ') ?: 'Paciente sin nombre';
    
    // Formatear monto si existe
    $formattedAmount = $event->monto ? '$' . number_format($event->monto, 2) : 'No especificado';
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Cita Asignada</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: black;
        }
        
        .content {
            padding: 30px 20px;
        }
        
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .appointment-card {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        
        .appointment-details {
            display: grid;
            gap: 12px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
            min-width: 100px;
            margin-right: 15px;
        }
        
        .detail-value {
            color: #212529;
            flex: 1;
        }
        
        .patient-section {
            background-color: #e8f4fd;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .patient-title {
            font-size: 18px;
            font-weight: 600;
            color: #0056b3;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .patient-title::before {
            content: "👤";
            margin-right: 8px;
        }
        
        .patient-info {
            font-size: 16px;
            color: #212529;
            margin-bottom: 8px;
        }
        
        .phone-info {
            display: flex;
            align-items: center;
            color: #6c757d;
            font-size: 14px;
        }
        
        .phone-info::before {
            content: "📞";
            margin-right: 8px;
        }
        
        .action-section {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        
        .action-text {
            color: #856404;
            font-weight: 500;
            margin: 0;
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }
        
        .system-signature {
            font-weight: 600;
            color: #495057;
        }
        
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            
            .content {
                padding: 20px 15px;
            }
            
            .detail-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .detail-label {
                margin-bottom: 5px;
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🗓️ Nueva Cita Asignada</h1>
        </div>
        
        <div class="content">
            <p class="greeting">
                Hola <strong>{{ $doctor?->nodoc }} {{ $doctor?->apdoc }}</strong>,
            </p>
            
            <p>Se te ha asignado una nueva cita médica. A continuación encontrarás todos los detalles:</p>
            
            <div class="appointment-card">
                <div class="appointment-details">
                    <div class="detail-item">
                        <span class="detail-label">Título:</span>
                        <span class="detail-value">{{ $event->title ?: 'Consulta médica' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Fecha inicio:</span>
                        <span class="detail-value">{{ $startDate }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Fecha fin:</span>
                        <span class="detail-value">{{ $endDate }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Monto:</span>
                        <span class="detail-value">{{ $formattedAmount }}</span>
                    </div>
                </div>
            </div>
            
            <div class="patient-section">
                <h3 class="patient-title">Información del Paciente</h3>
                <div class="patient-info">
                    <strong>Nombre:</strong> {{ $patientFullName }}
                </div>
                @if(!empty($patient?->phon))
                    <div class="phone-info">
                        <strong>Teléfono:</strong> {{ $patient->phon }}
                    </div>
                @endif
            </div>
            
            <div class="action-section">
                <p class="action-text">
                    ⏰ Por favor, revisa tu agenda y confirma tu disponibilidad para esta cita.
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p class="system-signature">— Sistema de Gestión de Turnos Médicos</p>
            <p><small>Este es un email automático, por favor no responder directamente.</small></p>
        </div>
    </div>
</body>
</html>