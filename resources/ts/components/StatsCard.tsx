import React from 'react';
import type { StatsCardProps } from '../interfaces/cards';

const StatsCard: React.FC<StatsCardProps> = ({ title, subtitle, icon, iconColor }) => {
  return (
    <div className="rounded bg-white mb-3 p-3">
        <div className="border-dashed d-flex align-items-center w-100 rounded overflow-hidden" style={{ minHeight: '100px' }}>
          <div className="d-flex justify-content-center align-items-center w-100 px-3 gap-3">
                <div className="text-center">
                    <h5 className="card-title fs-1">
                        {title }
                    </h5>
                    <h6 className="card-subtitle mb-2 text-muted">{ subtitle }</h6>
                </div>
                <div>
                    <div className="col-3 text-primary">
                        <i className={`bi bi-${icon} d-inline mx-1`} style={{ fontSize: '4rem', color: iconColor ?? 'inherit' }}></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
  );
};

export default StatsCard;
