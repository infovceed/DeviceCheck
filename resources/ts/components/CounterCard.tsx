import React from 'react';
import type { CounterCardProps } from '../interfaces/cards';

const CounterCard: React.FC<CounterCardProps> = ({ count, onIncrement }) => {
  return (
    <div className="p-4 border rounded-sm">
      <h2 className="text-lg font-medium mb-2">React + TypeScript en Laravel</h2>
      <p className="mb-2">¡Hola desde React! 🎉</p>
      <button
        className="px-3 py-1.5 bg-[#1b1b18] text-white rounded-sm hover:bg-black"
        onClick={onIncrement}
      >
        Contador: {count}
      </button>
    </div>
  );
};

export default CounterCard;
